<?php declare(strict_types = 1);

namespace Apitte\Core\Schema\Builder\Controller;

final class MethodRequest
{

	/** @var string|null */
	private $description;

	/** @var string|null */
	private $entity;

	/** @var bool */
	private $required = false;

	public function getDescription(): ?string
	{
		return $this->description;
	}

	public function setDescription(?string $description): void
	{
		$this->description = $description;
	}

	public function getEntity(): ?string
	{
		return $this->entity;
	}

	public function setEntity(?string $entity): void
	{
		$this->entity = $entity;
	}

	public function isRequired(): bool
	{
		return $this->required;
	}

	public function setRequired(bool $required): void
	{
		$this->required = $required;
	}

}
