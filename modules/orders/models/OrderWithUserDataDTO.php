<?php

declare(strict_types=1);

namespace app\modules\orders\models;

class OrderWithUserDataDTO
{
    private int $id;
    private string $user;
    private string $link;
    private int $quantity;
    private string $status;
    private string $mode;
    private int $created;
    private ServiceDTO $service;

    private const NAMES = [
        null => 'All',
        0 => 'Manual',
        1 => 'Auto'
    ];

    public function __construct(
        int $id,
        string $user,
        string $link,
        int $quantity,
        ServiceDTO $service,
        string $status,
        string $mode,
        int $created,
    ) {
        $this->id = $id;
        $this->user = $user;
        $this->link = $link;
        $this->quantity = $quantity;
        $this->service = $service;
        $this->status = $status;
        $this->mode = $mode;
        $this->created = $created;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getLink(): string
    {
        return $this->link;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @return int
     */
    public function getCreated(): int
    {
        return $this->created;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getModeName(): string
    {
        return self::NAMES[$this->mode];
    }

    public function getService(): ServiceDTO
    {
        return $this->service;
    }
}