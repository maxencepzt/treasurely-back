<?php

declare(strict_types=1);

namespace App\ApiPlatform;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Les sous-types d'une hiérarchie à table unique (PlayerTeam, GPSRiddle…) ne déclarent pas
 * d'#[ApiResource] : c'est la classe mère qui porte les opérations. API Platform les tient
 * pourtant pour des ressources (ResourceClassResolver::isResourceClass passe par is_a()) et,
 * dès qu'une propriété est typée avec l'un d'eux, la documentation Hydra leur demande une
 * opération qu'ils n'ont pas. Un sous-type sans métadonnées répond avec celles de son parent.
 */
final class InheritedResourceMetadataFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $decorated)
    {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $collection = $this->decorated->create($resourceClass);
        $parent = get_parent_class($resourceClass);

        return 0 === \count($collection) && false !== $parent ? $this->create($parent) : $collection;
    }
}
