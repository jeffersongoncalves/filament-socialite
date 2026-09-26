<?php

namespace JeffersonGoncalves\Filament\Socialite;

use Illuminate\Support\Str;

class Provider
{
    protected ?string $label = null;

    protected ?string $icon = null;

    protected string $color = 'gray';

    /** @var array<int, string> */
    protected array $scopes = [];

    /** @var array<string, mixed> */
    protected array $with = [];

    protected bool $stateless = false;

    final public function __construct(protected string $name) {}

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @param  array<int, string>  $scopes
     */
    public function scopes(array $scopes): static
    {
        $this->scopes = $scopes;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function with(array $parameters): static
    {
        $this->with = $parameters;

        return $this;
    }

    public function stateless(bool $condition = true): static
    {
        $this->stateless = $condition;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label ?? Str::headline($this->name);
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * @return array<int, string>
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @return array<string, mixed>
     */
    public function getWith(): array
    {
        return $this->with;
    }

    public function isStateless(): bool
    {
        return $this->stateless;
    }
}
