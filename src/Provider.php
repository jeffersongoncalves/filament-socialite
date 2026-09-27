<?php

namespace JeffersonGoncalves\Filament\Socialite;

use Illuminate\Support\Str;
use JeffersonGoncalves\Socialite\Provider as BaseProvider;

class Provider extends BaseProvider
{
    protected ?string $label = null;

    protected ?string $icon = null;

    protected string $color = 'gray';

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
}
