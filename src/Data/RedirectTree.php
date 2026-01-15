<?php

namespace Ndx\SimpleRedirect\Data;

use Statamic\Facades\File;
use Statamic\Facades\YAML;
use Statamic\Support\Arr;

class RedirectTree
{
    protected array $tree = [];

    protected static ?self $instance = null;

    public function __construct()
    {
        $this->load();
    }

    public static function instance(): self
    {
        return static::$instance ??= new static;
    }

    public static function clearInstance(): void
    {
        static::$instance = null;
    }

    public function tree(): array
    {
        return $this->tree;
    }

    public function setTree(array $tree): self
    {
        $this->tree = $tree;

        return $this;
    }

    public function append(string $id): self
    {
        if (! in_array($id, $this->tree)) {
            $this->tree[] = $id;
        }

        return $this;
    }

    public function remove(string $id): self
    {
        $this->tree = array_values(array_filter($this->tree, fn ($item) => $item !== $id));

        return $this;
    }

    public function move(string $id, int $position): self
    {
        $this->remove($id);

        array_splice($this->tree, $position, 0, [$id]);

        return $this;
    }

    public function save(): bool
    {
        $this->ensureDirectoryExists();

        $contents = YAML::dump(['tree' => $this->tree]);

        File::put($this->path(), $contents);

        return true;
    }

    public function load(): self
    {
        if (! File::exists($this->path())) {
            $this->tree = [];

            return $this;
        }

        $contents = File::get($this->path());
        $data     = YAML::parse($contents);

        $this->tree = Arr::get($data, 'tree', []);

        return $this;
    }

    public function exists(): bool
    {
        return File::exists($this->path());
    }

    public function path(): string
    {
        return base_path('content/trees/redirects.yaml');
    }

    protected function ensureDirectoryExists(): void
    {
        $directory = dirname($this->path());

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }
}
