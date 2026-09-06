<?php

namespace App\Service\Designer;

use App\Dto\Designer\HuntInput;
use App\Entity\DesignerTeam;
use App\Entity\TreasureHunt;
use App\Entity\User;
use App\Repository\DesignerTeamRepository;
use App\Repository\HuntTypeRepository;
use App\Service\Designer\Exception\HuntValidationException;
use App\Service\ImageUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Cas d'usage de la façade de conception : créer et modifier une chasse à partir du
 * formulaire. Le contrôleur ne fait que traduire la requête et la réponse.
 */
final class HuntEditor
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly DesignerTeamRepository $designerTeamRepository,
        private readonly HuntTypeRepository $huntTypeRepository,
        private readonly ImageUploadService $imageUploadService,
        private readonly HuntRiddleSynchronizer $riddleSynchronizer,
        private readonly WorkflowInterface $treasureHuntWorkflow,
    ) {
    }

    /**
     * @throws HuntValidationException
     * @throws Exception\HuntConflictException
     * @throws AccessDeniedException           si l'utilisateur n'appartient pas à l'équipe visée
     */
    public function create(HuntInput $input, User $owner): TreasureHunt
    {
        $this->assertInputShape($input);

        $hunt = (new TreasureHunt())
            ->setOwner($owner)
            ->setDesignerTeam($this->resolveTeam($input->designerTeamId, $owner));
        $this->entityManager->persist($hunt);

        $this->fill($hunt, $input, $owner);
        $this->entityManager->flush();

        return $hunt;
    }

    /**
     * @throws HuntValidationException
     * @throws Exception\HuntConflictException
     * @throws AccessDeniedException           si l'éditeur veut rattacher la chasse à une équipe qui n'est pas la sienne
     */
    public function update(TreasureHunt $hunt, HuntInput $input, User $editor): void
    {
        $this->assertInputShape($input);

        if ($input->designerTeamId !== $hunt->getDesignerTeam()?->getId()) {
            $hunt->setDesignerTeam($this->resolveTeam($input->designerTeamId, $editor));
        }

        $this->fill($hunt, $input, $editor);
        $this->entityManager->flush();
    }

    private function fill(TreasureHunt $hunt, HuntInput $input, User $actor): void
    {
        $hunt
            ->setTitle($input->title)
            ->setDescription($input->description)
            ->setDifficulty($input->difficulty)
            ->setEstimatedTime($input->estimatedTime)
            ->setLocation($input->location);

        $this->syncHuntTypes($hunt, $input->huntTypeIds);

        if (null !== $input->image) {
            try {
                $hunt->setImage($this->imageUploadService->uploadImage($input->image));
            } catch (\InvalidArgumentException $e) {
                throw new HuntValidationException([$e->getMessage()]);
            }
        }

        $riddles = $this->riddleSynchronizer->synchronize($hunt, $input->riddles);
        $hunt->setRiddleCount(count($riddles));

        $violations = new ConstraintViolationList($this->validator->validate($hunt));
        foreach ($riddles as $riddle) {
            $violations->addAll($this->validator->validate($riddle));
        }
        if (count($violations) > 0) {
            throw HuntValidationException::fromViolations($violations);
        }

        if ($input->wantsPublication()) {
            $this->publish($hunt);
        }
    }

    /**
     * Les contraintes de forme du DTO sont vérifiées avant toute correspondance, pour
     * qu'aucune entité à moitié remplie ne soit construite sur une charge utile invalide.
     */
    private function assertInputShape(HuntInput $input): void
    {
        $violations = $this->validator->validate($input);
        if (count($violations) > 0) {
            throw HuntValidationException::fromViolations($violations);
        }
    }

    private function resolveTeam(int $designerTeamId, User $user): DesignerTeam
    {
        $team = $this->designerTeamRepository->find($designerTeamId);
        if (null === $team) {
            throw new HuntValidationException(['L\'équipe choisie n\'existe pas.']);
        }
        if ($team->getOwner() !== $user && !$team->hasMember($user)) {
            throw new AccessDeniedException('Vous ne faites pas partie de cette équipe.');
        }

        return $team;
    }

    /**
     * @param int[] $huntTypeIds
     */
    private function syncHuntTypes(TreasureHunt $hunt, array $huntTypeIds): void
    {
        $wanted = [] === $huntTypeIds ? [] : $this->huntTypeRepository->findBy(['id' => $huntTypeIds]);

        foreach ($hunt->getHuntType() as $current) {
            if (!in_array($current, $wanted, true)) {
                $hunt->removeHuntType($current);
            }
        }
        foreach ($wanted as $huntType) {
            $hunt->addHuntType($huntType);
        }
    }

    /**
     * Le statut ne se change que par le workflow, qui garantit les transitions permises.
     * Une chasse déjà ouverte reste ouverte ; une chasse fermée est republiée.
     */
    private function publish(TreasureHunt $hunt): void
    {
        foreach (['publish', 'republish'] as $transition) {
            if ($this->treasureHuntWorkflow->can($hunt, $transition)) {
                $this->treasureHuntWorkflow->apply($hunt, $transition);

                return;
            }
        }
    }
}
