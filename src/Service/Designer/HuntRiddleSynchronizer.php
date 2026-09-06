<?php

namespace App\Service\Designer;

use App\Dto\Designer\RiddleInput;
use App\Entity\Riddle;
use App\Entity\TreasureHunt;
use App\Repository\ParticipateRiddleRepository;
use App\Repository\RiddleRepository;
use App\Service\Designer\Exception\HuntConflictException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Rapproche les énigmes soumises par le formulaire des énigmes en base, au lieu de
 * tout supprimer et recréer. Une énigme connue est mise à jour sur place, ce qui
 * préserve les participations qui s'y rattachent ; une énigme absente de la charge
 * utile est supprimée si la politique de la chasse le permet.
 *
 * Politique par état de la chasse :
 *   draft  : tout est permis, aucune participation n'existe.
 *   opened : ajout libre ; suppression et changement de type refusés sur une énigme jouée.
 *   closed : structure figée, seules les mises à jour de champs passent.
 */
final class HuntRiddleSynchronizer
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RiddleRepository $riddleRepository,
        private readonly ParticipateRiddleRepository $participateRiddleRepository,
        private readonly RiddleMapper $mapper,
    ) {
    }

    /**
     * @param RiddleInput[] $inputs dans l'ordre voulu par le concepteur
     *
     * @return Riddle[] les énigmes de la chasse après synchronisation, dans l'ordre
     *
     * @throws HuntConflictException
     */
    public function synchronize(TreasureHunt $hunt, array $inputs): array
    {
        $existing = [];
        if (null !== $hunt->getId()) { // une chasse en cours de création n'a encore rien à rapprocher
            foreach ($this->riddleRepository->findByTreasureHunt($hunt) as $riddle) {
                $existing[$riddle->getId()] = $riddle;
            }
        }
        $played = $this->participateRiddleRepository->countByRiddle(array_values($existing));
        $frozen = TreasureHunt::STATE_CLOSED === $hunt->getStatus();

        $kept = [];
        $result = [];
        $order = 1;
        foreach ($inputs as $input) {
            $riddle = null !== $input->id ? ($existing[$input->id] ?? null) : null;

            if (null === $riddle) {
                if ($frozen) {
                    throw new HuntConflictException('Une chasse fermée ne peut plus recevoir de nouvelles énigmes.');
                }
                $riddle = $this->mapper->create($input, $hunt, $order++);
                $this->entityManager->persist($riddle);
                $hunt->addRiddle($riddle);
                $result[] = $riddle;
                continue;
            }

            if ($riddle->getType() !== $input->type) {
                $kept[$input->id] = true; // retirée par replace(), pas par la boucle de suppression
                $riddle = $this->replace($riddle, $input, $hunt, $order++, $played, $frozen);
                $result[] = $riddle;
                continue;
            }

            $this->mapper->apply($riddle, $input, $order++);
            $kept[$input->id] = true;
            $result[] = $riddle;
        }

        foreach (array_diff_key($existing, $kept) as $riddle) {
            $this->assertDeletable($riddle, $hunt, $played, $frozen);
            $hunt->removeRiddle($riddle);
            $this->entityManager->remove($riddle);
        }

        return $result;
    }

    /**
     * Le changement de type impose une suppression puis une création, donc la perte
     * des participations : il n'est accepté que là où il n'y en a pas.
     *
     * @param array<int, int> $played
     */
    private function replace(Riddle $riddle, RiddleInput $input, TreasureHunt $hunt, int $order, array $played, bool $frozen): Riddle
    {
        if ($frozen) {
            throw new HuntConflictException(sprintf('La chasse est fermée : le type de l\'énigme « %s » ne peut plus changer.', $riddle->getTitle()));
        }
        if (($played[$riddle->getId()] ?? 0) > 0) {
            throw new HuntConflictException(sprintf('L\'énigme « %s » a déjà été jouée : son type ne peut plus changer. Supprimez-la et créez-en une nouvelle si c\'est bien ce que vous voulez.', $riddle->getTitle()));
        }

        $hunt->removeRiddle($riddle);
        $this->entityManager->remove($riddle);

        $replacement = $this->mapper->create($input, $hunt, $order);
        $this->entityManager->persist($replacement);
        $hunt->addRiddle($replacement);

        return $replacement;
    }

    /**
     * @param array<int, int> $played
     */
    private function assertDeletable(Riddle $riddle, TreasureHunt $hunt, array $played, bool $frozen): void
    {
        if ($frozen) {
            throw new HuntConflictException(sprintf('La chasse est fermée : l\'énigme « %s » ne peut plus être supprimée.', $riddle->getTitle()));
        }
        if (TreasureHunt::STATE_DRAFT !== $hunt->getStatus() && ($played[$riddle->getId()] ?? 0) > 0) {
            throw new HuntConflictException(sprintf('L\'énigme « %s » a déjà été jouée et ne peut plus être supprimée.', $riddle->getTitle()));
        }
    }
}
