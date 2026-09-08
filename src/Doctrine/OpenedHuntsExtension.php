<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\TreasureHunt;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * La liste des chasses de l'API sert à en trouver une à jouer : un joueur n'y voit que les
 * chasses ouvertes. Les brouillons et les chasses fermées restent lisibles à l'unité (sécurité
 * de l'opération Get) et dans la façade concepteur ; un administrateur voit tout.
 */
final readonly class OpenedHuntsExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private Security $security)
    {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (TreasureHunt::class !== $resourceClass || $this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $parameter = $queryNameGenerator->generateParameterName('status');
        $queryBuilder
            ->andWhere(sprintf('%s.status = :%s', $alias, $parameter))
            ->setParameter($parameter, TreasureHunt::STATE_OPENED);
    }
}
