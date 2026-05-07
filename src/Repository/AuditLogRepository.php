<?php

namespace App\Repository;

use App\Core\MongoDatabase;
use MongoDB\BSON\UTCDateTime;
use Throwable;

/**
 * Stores application audit events in MongoDB.
 */
class AuditLogRepository
{
    /**
     * MongoDB collection name for audit events.
     */
    private string $collection = 'audit_logs';

    /**
     * Creates the repository for the audit_logs collection.
     */
    public function __construct()
    {
    }

    /**
     * Writes an audit event to MongoDB when the driver is available.
     *
     * @param array<string, mixed> $payload
     */
    public function log(string $event, array $payload = []): void
    {
        if (!MongoDatabase::isAvailable()) {
            $this->rememberError('MongoDB PHP library or extension is not available.');
            return;
        }

        try {
            $message = $payload['message'] ?? $this->messageFor($event, $payload);

            MongoDatabase::getClient()
                ->selectCollection(MongoDatabase::getDatabaseName(), $this->collection)
                ->insertOne([
                'event' => $event,
                'message' => $message,
                'payload' => $payload,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => new UTCDateTime(),
            ]);
            $this->clearError();
        } catch (Throwable $e) {
            $this->rememberError($e->getMessage());
            // Audit logging must not break the main relational workflow.
        }
    }

    /**
     * Writes a test document to check MongoDB connectivity.
     */
    public function testConnection(): bool
    {
        $this->log('mongo_test', [
            'message' => 'Выполнена тестовая запись в MongoDB.',
        ]);

        return !isset($_SESSION['mongo_error']);
    }

    /**
     * Returns recent audit events for diagnostics.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recent(int $limit = 20): array
    {
        if (!MongoDatabase::isAvailable()) {
            return [];
        }

        try {
            $cursor = MongoDatabase::getClient()
                ->selectCollection(MongoDatabase::getDatabaseName(), $this->collection)
                ->find([], [
                    'limit' => $limit,
                    'sort' => ['_id' => -1],
                ]);

            return json_decode(json_encode($cursor->toArray()), true) ?: [];
        } catch (Throwable $e) {
            $this->rememberError($e->getMessage());
            return [];
        }
    }

    /**
     * Stores the latest MongoDB error when debug mode is enabled.
     */
    private function rememberError(string $message): void
    {
        if (!MongoDatabase::isDebugEnabled()) {
            return;
        }

        $_SESSION['mongo_error'] = $message;
    }

    /**
     * Clears the latest MongoDB error.
     */
    private function clearError(): void
    {
        unset($_SESSION['mongo_error']);
    }

    /**
     * Builds a human-readable audit message.
     *
     * @param array<string, mixed> $payload
     */
    private function messageFor(string $event, array $payload): string
    {
        $user = $this->displayName($payload['user'] ?? null, $payload['login'] ?? null);
        $admin = $this->displayName($payload['admin'] ?? null, $payload['admin_email'] ?? null);

        return match ($event) {
            'login' => sprintf('%s вошел в систему.', $user),
            'registration' => sprintf('Пользователь %s зарегистрировался.', $user),
            'rental_created' => sprintf(
                'Пользователь %s арендовал автомобиль %s. Стоимость: %s лей.',
                $user,
                $this->carName($payload['car'] ?? null),
                $payload['total_cost'] ?? '0'
            ),
            'payment_created' => sprintf(
                'Пользователь %s оплатил аренду #%s на сумму %s лей.',
                $user,
                $payload['rental_id'] ?? 'unknown',
                $payload['amount'] ?? '0'
            ),
            'rental_finished' => sprintf(
                'Пользователь %s завершил аренду #%s автомобиля %s.',
                $user,
                $payload['rental_id'] ?? 'unknown',
                $this->carName($payload['car'] ?? null)
            ),
            'admin_car_created' => sprintf('%s добавил автомобиль %s.', $admin, $this->carName($payload['car'] ?? null)),
            'admin_car_updated' => sprintf('%s изменил автомобиль %s.', $admin, $this->carName($payload['car'] ?? null)),
            'admin_car_deleted' => sprintf('%s удалил автомобиль %s.', $admin, $this->carName($payload['car'] ?? null)),
            'admin_client_created' => sprintf('%s добавил клиента %s.', $admin, $this->displayName($payload['client'] ?? null)),
            'admin_client_updated' => sprintf('%s изменил данные клиента %s.', $admin, $this->displayName($payload['client'] ?? null)),
            'admin_client_deleted' => sprintf('%s удалил клиента %s.', $admin, $this->displayName($payload['client'] ?? null)),
            'admin_payment_created' => sprintf(
                '%s добавил оплату #%s на сумму %s лей.',
                $admin,
                $payload['payment_id'] ?? 'unknown',
                $payload['payment']['amount'] ?? '0'
            ),
            'admin_payment_updated' => sprintf('%s изменил оплату #%s.', $admin, $payload['payment_id'] ?? 'unknown'),
            'admin_payment_deleted' => sprintf('%s удалил оплату #%s.', $admin, $payload['payment_id'] ?? 'unknown'),
            'admin_rental_created' => sprintf(
                '%s добавил аренду #%s для клиента #%s и автомобиля #%s.',
                $admin,
                $payload['rental_id'] ?? 'unknown',
                $payload['rental']['client_id'] ?? 'unknown',
                $payload['rental']['car_id'] ?? 'unknown'
            ),
            'admin_rental_updated' => sprintf('%s изменил аренду #%s.', $admin, $payload['rental_id'] ?? 'unknown'),
            'admin_rental_deleted' => sprintf('%s удалил аренду #%s.', $admin, $payload['rental_id'] ?? 'unknown'),
            'admin_fine_created' => sprintf(
                '%s добавил штраф "%s" клиенту #%s.',
                $admin,
                $payload['fine']['title'] ?? 'Без названия',
                $payload['fine']['client_id'] ?? 'unknown'
            ),
            'admin_fine_updated' => sprintf('%s изменил штраф #%s.', $admin, $payload['fine_id'] ?? 'unknown'),
            'admin_fine_deleted' => sprintf('%s удалил штраф #%s.', $admin, $payload['fine_id'] ?? 'unknown'),
            'mongo_test' => 'Выполнена тестовая запись в MongoDB.',
            default => sprintf('Выполнено событие %s.', $event),
        };
    }

    /**
     * Returns a readable person name from an array or fallback string.
     *
     * @param mixed $person
     */
    private function displayName(mixed $person, ?string $fallback = null): string
    {
        if (is_array($person)) {
            return (string)($person['full_name'] ?? $person['name'] ?? $person['email'] ?? $fallback ?? 'Неизвестный пользователь');
        }

        return $fallback ?? 'Неизвестный пользователь';
    }

    /**
     * Returns a readable car name from a car row.
     *
     * @param mixed $car
     */
    private function carName(mixed $car): string
    {
        if (!is_array($car)) {
            return 'автомобиль не найден';
        }

        return trim(($car['brand'] ?? '') . ' ' . ($car['model'] ?? '')) ?: 'автомобиль #' . ($car['id'] ?? 'unknown');
    }
}
