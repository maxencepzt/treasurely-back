<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\PlayerTeam;
use App\Entity\Team;
use Doctrine\ORM\QueryBuilder;

/**
 * L'annuaire `GET /player_teams` ne liste que les équipes de joueurs : les équipes de
 * concepteurs se recrutent dans la façade du back, pas par l'application.
 */
final class PlayerTeamsExtension implements QueryCollectionExtensionInterface
{
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (Team::class !== $resourceClass || 'player_teams' !== $operation?->getName()) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->andWhere(sprintf('%s INSTANCE OF %s', $alias, PlayerTeam::class));
    }
}
