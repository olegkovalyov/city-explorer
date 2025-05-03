<?php

namespace App\Support;

use App\Enums\ErrorCode;
use Throwable; // Import Throwable

/**
 * Represents the outcome of an operation, which can be either success or failure.
 */
final readonly class Result
{
    private function __construct(
        public bool $isSuccess,
        public mixed $value = null,
        public ?ErrorCode $errorCode = null,
        public ?string $errorMessage = null,
        public ?array $errorContext = null
    ) {}

    /**
     * Create a success result.
     *
     * @param mixed|null $value The value to return on success (optional).
     * @return self
     */
    public static function success(mixed $value = null): self
    {
        return new self(isSuccess: true, value: $value);
    }

    /**
     * Create a failure result.
     *
     * @param ErrorCode $errorCode The specific error code.
     * @param string|null $message Optional descriptive error message.
     * @param array|null $context Optional additional context about the error.
     * @return self
     */
    public static function failure(ErrorCode $errorCode, ?string $message = null, ?array $context = null): self
    {
        // Use default message from enum if no specific message is provided
        $errorMessage = $message ?? $errorCode->message();
        return new self(
            isSuccess: false,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            errorContext: $context
        );
    }

    /**
     * Create a failure result from an exception.
     *
     * @param Throwable $exception The exception caught.
     * @param ErrorCode $errorCode The error code to associate (e.g., DATABASE_ERROR, UNEXPECTED_ERROR).
     * @param string|null $overrideMessage Optional message to override the exception's message.
     * @return self
     */
    public static function failureFromException(Throwable $exception, ErrorCode $errorCode, ?string $overrideMessage = null): self
    {
        $message = $overrideMessage ?? $exception->getMessage();
        // Optionally log the exception here if it's not logged elsewhere
        // Log::error($errorCode->value . ': ' . $message, ['exception' => $exception]);
        return self::failure($errorCode, $message);
    }

    /**
     * Check if the result represents a failure.
     */
    public function isFailure(): bool
    {
        return !$this->isSuccess;
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    /**
     * Get the value if the result is successful.
     *
     * @return mixed Returns the value on success, null otherwise (or throws exception).
     * @throws \LogicException If called on a failure result.
     */
    public function getValue(): mixed
    {
        if ($this->isFailure()) {
            throw new \LogicException('Cannot get value from a failure result.');
        }
        return $this->value;
    }

    /**
     * Get the error code if the result is a failure.
     *
     * @return ErrorCode|null Returns the ErrorCode on failure, null otherwise.
     */
    public function getErrorCode(): ?ErrorCode
    {
        return $this->errorCode;
    }

    /**
     * Get the error message if the result is a failure.
     *
     * @return string|null Returns the error message on failure, null otherwise.
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * Get the additional error context if the result is a failure.
     *
     * @return array|null Returns the error context array on failure, null otherwise.
     */
    public function getErrorContext(): ?array
    {
        return $this->errorContext;
    }
}
