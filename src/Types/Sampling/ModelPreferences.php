<?php

declare(strict_types=1);

namespace Dtyq\PhpMcp\Types\Sampling;

use Dtyq\PhpMcp\Shared\Exceptions\ValidationError;
use Dtyq\PhpMcp\Shared\Utilities\JsonUtils;

/**
 * Represents model selection preferences for sampling requests.
 * 
 * This class encapsulates preferences for model selection including
 * model hints, cost priority, speed priority, and intelligence priority.
 */
class ModelPreferences
{
    /** @var ModelHint[] */
    private array $hints;
    private ?float $costPriority;
    private ?float $speedPriority;
    private ?float $intelligencePriority;

    /**
     * Create new model preferences.
     *
     * @param ModelHint[] $hints Model name hints
     * @param float|null $costPriority Cost importance (0.0-1.0)
     * @param float|null $speedPriority Speed importance (0.0-1.0)
     * @param float|null $intelligencePriority Intelligence importance (0.0-1.0)
     * @throws ValidationError If parameters are invalid
     */
    public function __construct(
        array $hints = [],
        ?float $costPriority = null,
        ?float $speedPriority = null,
        ?float $intelligencePriority = null
    ) {
        $this->setHints($hints);
        $this->setCostPriority($costPriority);
        $this->setSpeedPriority($speedPriority);
        $this->setIntelligencePriority($intelligencePriority);
    }

    /**
     * Create preferences from array data.
     *
     * @param array<string, mixed> $data The preferences data
     * @return self
     * @throws ValidationError If data is invalid
     */
    public static function fromArray(array $data): self
    {
        $hints = [];
        if (isset($data['hints'])) {
            if (!is_array($data['hints'])) {
                throw ValidationError::invalidFieldType('hints', 'array', gettype($data['hints']));
            }

            foreach ($data['hints'] as $index => $hintData) {
                if (!is_array($hintData)) {
                    throw ValidationError::invalidFieldType("hints[{$index}]", 'array', gettype($hintData));
                }
                $hints[] = ModelHint::fromArray($hintData);
            }
        }

        return new self(
            $hints,
            $data['costPriority'] ?? null,
            $data['speedPriority'] ?? null,
            $data['intelligencePriority'] ?? null
        );
    }

    /**
     * Create preferences with model hints.
     *
     * @param string[] $modelNames Model name hints
     * @return self
     */
    public static function createWithHints(array $modelNames): self
    {
        $hints = array_map(fn($name) => new ModelHint($name), $modelNames);
        return new self($hints);
    }

    /**
     * Create preferences with priorities.
     *
     * @param float|null $costPriority Cost importance (0.0-1.0)
     * @param float|null $speedPriority Speed importance (0.0-1.0)
     * @param float|null $intelligencePriority Intelligence importance (0.0-1.0)
     * @return self
     */
    public static function withPriorities(
        ?float $costPriority = null,
        ?float $speedPriority = null,
        ?float $intelligencePriority = null
    ): self {
        return new self([], $costPriority, $speedPriority, $intelligencePriority);
    }

    /**
     * Get the model hints.
     *
     * @return ModelHint[]
     */
    public function getHints(): array
    {
        return $this->hints;
    }

    /**
     * Get the cost priority.
     *
     * @return float|null
     */
    public function getCostPriority(): ?float
    {
        return $this->costPriority;
    }

    /**
     * Get the speed priority.
     *
     * @return float|null
     */
    public function getSpeedPriority(): ?float
    {
        return $this->speedPriority;
    }

    /**
     * Get the intelligence priority.
     *
     * @return float|null
     */
    public function getIntelligencePriority(): ?float
    {
        return $this->intelligencePriority;
    }

    /**
     * Check if hints are set.
     *
     * @return bool
     */
    public function hasHints(): bool
    {
        return !empty($this->hints);
    }

    /**
     * Check if cost priority is set.
     *
     * @return bool
     */
    public function hasCostPriority(): bool
    {
        return $this->costPriority !== null;
    }

    /**
     * Check if speed priority is set.
     *
     * @return bool
     */
    public function hasSpeedPriority(): bool
    {
        return $this->speedPriority !== null;
    }

    /**
     * Check if intelligence priority is set.
     *
     * @return bool
     */
    public function hasIntelligencePriority(): bool
    {
        return $this->intelligencePriority !== null;
    }

    /**
     * Get the number of hints.
     *
     * @return int
     */
    public function getHintCount(): int
    {
        return count($this->hints);
    }

    /**
     * Get hint names as strings.
     *
     * @return string[]
     */
    public function getHintNames(): array
    {
        return array_map(fn($hint) => $hint->getName(), $this->hints);
    }

    /**
     * Set the model hints.
     *
     * @param ModelHint[] $hints The hints
     * @throws ValidationError If hints are invalid
     */
    public function setHints(array $hints): void
    {
        foreach ($hints as $index => $hint) {
            if (!$hint instanceof ModelHint) {
                throw ValidationError::invalidFieldType(
                    "hints[{$index}]",
                    ModelHint::class,
                    gettype($hint)
                );
            }
        }

        $this->hints = array_values($hints);
    }

    /**
     * Set the cost priority.
     *
     * @param float|null $costPriority The priority (0.0-1.0)
     * @throws ValidationError If priority is invalid
     */
    public function setCostPriority(?float $costPriority): void
    {
        if ($costPriority !== null && ($costPriority < 0.0 || $costPriority > 1.0)) {
            throw ValidationError::invalidFieldValue('costPriority', 'must be between 0.0 and 1.0');
        }

        $this->costPriority = $costPriority;
    }

    /**
     * Set the speed priority.
     *
     * @param float|null $speedPriority The priority (0.0-1.0)
     * @throws ValidationError If priority is invalid
     */
    public function setSpeedPriority(?float $speedPriority): void
    {
        if ($speedPriority !== null && ($speedPriority < 0.0 || $speedPriority > 1.0)) {
            throw ValidationError::invalidFieldValue('speedPriority', 'must be between 0.0 and 1.0');
        }

        $this->speedPriority = $speedPriority;
    }

    /**
     * Set the intelligence priority.
     *
     * @param float|null $intelligencePriority The priority (0.0-1.0)
     * @throws ValidationError If priority is invalid
     */
    public function setIntelligencePriority(?float $intelligencePriority): void
    {
        if ($intelligencePriority !== null && ($intelligencePriority < 0.0 || $intelligencePriority > 1.0)) {
            throw ValidationError::invalidFieldValue('intelligencePriority', 'must be between 0.0 and 1.0');
        }

        $this->intelligencePriority = $intelligencePriority;
    }

    /**
     * Add a model hint.
     *
     * @param ModelHint $hint The hint to add
     */
    public function addHint(ModelHint $hint): void
    {
        $this->hints[] = $hint;
    }

    /**
     * Add a model hint by name.
     *
     * @param string $name The model name
     */
    public function addHintByName(string $name): void
    {
        $this->hints[] = new ModelHint($name);
    }

    /**
     * Create new preferences with different hints.
     *
     * @param ModelHint[] $hints The new hints
     * @return self
     */
    public function withHints(array $hints): self
    {
        $new = clone $this;
        $new->setHints($hints);
        return $new;
    }

    /**
     * Create new preferences with different cost priority.
     *
     * @param float|null $costPriority The new cost priority
     * @return self
     */
    public function withCostPriority(?float $costPriority): self
    {
        $new = clone $this;
        $new->setCostPriority($costPriority);
        return $new;
    }

    /**
     * Create new preferences with different speed priority.
     *
     * @param float|null $speedPriority The new speed priority
     * @return self
     */
    public function withSpeedPriority(?float $speedPriority): self
    {
        $new = clone $this;
        $new->setSpeedPriority($speedPriority);
        return $new;
    }

    /**
     * Create new preferences with different intelligence priority.
     *
     * @param float|null $intelligencePriority The new intelligence priority
     * @return self
     */
    public function withIntelligencePriority(?float $intelligencePriority): self
    {
        $new = clone $this;
        $new->setIntelligencePriority($intelligencePriority);
        return $new;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];

        if (!empty($this->hints)) {
            $result['hints'] = array_map(fn($hint) => $hint->toArray(), $this->hints);
        }

        if ($this->costPriority !== null) {
            $result['costPriority'] = $this->costPriority;
        }

        if ($this->speedPriority !== null) {
            $result['speedPriority'] = $this->speedPriority;
        }

        if ($this->intelligencePriority !== null) {
            $result['intelligencePriority'] = $this->intelligencePriority;
        }

        return $result;
    }

    /**
     * Convert to JSON string.
     *
     * @return string
     */
    public function toJson(): string
    {
        return JsonUtils::encode($this->toArray());
    }
} 