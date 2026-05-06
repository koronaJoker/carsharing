<?php
namespace App\Classes;
use Exception;
class Client {
    private string $name;
    private string $email;
    private string $idnp;
    private string $password;
    private string $confirm_password;
    private string $phone;

    public function __construct(
        string $name,
        string $email,
        string $idnp,
        string $password,
        string $confirm_password,
        string $phone
    ) {
        $this->setName($name);
        $this->setEmail($email);
        $this->setIdnp($idnp);
        $this->setPassword($password);
        $this->setConfirmPassword($confirm_password);
        $this->setPhone($phone);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        if (trim($name) === '') {
            throw new Exception("Name cannot be empty.");
        }

        $this->name = trim($name);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        $this->email = trim($email);
    }

    public function getIdnp(): string
    {
        return $this->idnp;
    }

    public function setIdnp(string $idnp): void
    {
        if (!preg_match('/^\d{13}$/', $idnp)) {
            throw new Exception("IDNP must contain exactly 13 digits.");
        }

        $this->idnp = $idnp;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        if (strlen($password) < 6) {
            throw new Exception("Password must be at least 6 characters.");
        }

        $this->password = $password;
    }

    public function getConfirmPassword(): string
    {
        return $this->confirm_password;
    }

    public function setConfirmPassword(string $confirm_password): void
    {
        if ($confirm_password === '') {
            throw new Exception("Confirm password cannot be empty.");
        }

        if ($confirm_password !== $this->password) {
            throw new Exception("Passwords do not match.");
        }

        $this->confirm_password = $confirm_password;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        if (!preg_match('/^\+?[0-9]{8,15}$/', $phone)) {
            throw new Exception("Phone must be 8-15 digits and may start with +.");
        }

        $this->phone = $phone;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'idnp' => $this->idnp,
            'password' => $this->password,
            'confirm_password' => $this->confirm_password,
            'phone' => $this->phone,
        ];
    }
}
