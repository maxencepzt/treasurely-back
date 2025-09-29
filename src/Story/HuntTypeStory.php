<?php

namespace App\Story;

use App\Factory\HuntTypeFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Zenstruck\Foundry\Story;

use function Zenstruck\Foundry\Persistence\flush_after;

final class HuntTypeStory extends Story
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private readonly string $projectDir)
    {
    }

    public function build(): void
    {
        $path = implode(DIRECTORY_SEPARATOR, [$this->projectDir, 'data', 'hunt_types.json']);
        $data = json_decode(file_get_contents($path));

        $this->addState('huntType_without_hunts', HuntTypeFactory::createOne(['title' => $data[0]]));
        flush_after(function () use ($data) {
            for ($i = 1; $i < count($data); ++$i) {
                $this->addToPool('huntTypes', HuntTypeFactory::createOne(['title' => $data[$i]]));
            }
        });
    }
}
