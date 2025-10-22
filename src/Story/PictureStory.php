<?php

namespace App\Story;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;
use Zenstruck\Foundry\Story;

use function Zenstruck\Foundry\Persistence\flush_after;

final class PictureStory extends Story
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private readonly string $projectDir)
    {
    }

    public function build(): void
    {
        $path = implode(DIRECTORY_SEPARATOR, [$this->projectDir, 'data', 'pictures']);
        $finder = new Finder();
        $finder->files()
            ->in($path)
            ->name('/\.(jpg|jpeg)$/i');

        flush_after(function () use ($finder) {
            foreach ($finder as $file) {
                $this->addToPool('pictures', $file->getContents());
            }
        });
    }
}
