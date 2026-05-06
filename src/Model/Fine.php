<?php

namespace App\Model;

class Fine
{
    private ?int $id = null;
    private int $clientId;
    private ?int $rentalId = null;
    private string $title;
    private ?string $description = null;
    private float $amount;
    private float $ratingPenalty = 0.1;
    private string $status = 'unpaid';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        if ($id !== null && $id <= 0) {
            throw new \InvalidArgumentException('ID должен быть положительным');
        }

        $this->id = $id;
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }

    public function setClientId(int $clientId): void
    {
        if ($clientId <= 0) {
            throw new \InvalidArgumentException('client_id должен быть положительным');
        }

        $this->clientId = $clientId;
    }

    public function getRentalId(): ?int
    {
        return $this->rentalId;
    }

    public function setRentalId(?int $rentalId): void
    {
        if ($rentalId !== null && $rentalId <= 0) {
            throw new \InvalidArgumentException('rental_id должен быть положительным');
        }

        $this->rentalId = $rentalId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $title = trim($title);

        if ($title === '') {
            throw new \InvalidArgumentException('Название штрафа не может быть пустым');
        }

        if (mb_strlen($title) > 100) {
            throw new \InvalidArgumentException('Название штрафа слишком длинное');
        }

        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description !== null ? trim($description) : null;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Сумма штрафа должна быть больше 0');
        }

        $this->amount = $amount;
    }

    public function getRatingPenalty(): float
    {
        return $this->ratingPenalty;
    }

    public function setRatingPenalty(float $ratingPenalty): void
    {
        if ($ratingPenalty < 0 || $ratingPenalty > 5) {
            throw new \InvalidArgumentException('Штраф рейтинга должен быть от 0 до 5');
        }

        $this->ratingPenalty = $ratingPenalty;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $allowed = ['unpaid', 'paid', 'cancelled'];

        if (!in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException('Неверный статус штрафа');
        }

        $this->status = $status;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->clientId,
            'rental_id' => $this->rentalId,
            'title' => $this->title,
            'description' => $this->description,
            'amount' => $this->amount,
            'rating_penalty' => $this->ratingPenalty,
            'status' => $this->status,
        ];
    }
}
