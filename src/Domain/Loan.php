<?php

declare(strict_types=1);
namespace BookFlow\Domain;

final class Loan
{
    public function __construct(
        public readonly string $id,
        public readonly string $bookId,
        public readonly string $borrowerName,
        public readonly string $loanedAt,
        public readonly string $dueDate,
        public ?string $returnedAt = null
    ) {
        if ($bookId === '' || trim($borrowerName) === '') throw new BusinessRuleException('Book and borrower are required.');
        try { new \DateTimeImmutable($dueDate); } catch (\Throwable) { throw new BusinessRuleException('Due date is invalid.'); }
    }

    public static function create(string $bookId, string $borrowerName, string $dueDate): self
    {
        try { $parsedDueDate = new \DateTimeImmutable($dueDate); } catch (\Throwable) { throw new BusinessRuleException('Due date is invalid.'); }
        if ($parsedDueDate <= new \DateTimeImmutable('now', new \DateTimeZone('UTC'))) {
            throw new BusinessRuleException('Due date must be in the future.');
        }
        return new self(bin2hex(random_bytes(16)), $bookId, trim($borrowerName), gmdate(DATE_ATOM), $dueDate);
    }

    public function isReturned(): bool { return $this->returnedAt !== null; }

    public function markReturned(): void
    {
        if ($this->isReturned()) throw new BusinessRuleException('This loan has already been returned.');
        $this->returnedAt = gmdate(DATE_ATOM);
    }

    public function toArray(): array
    {
        return ['id'=>$this->id, 'bookId'=>$this->bookId, 'borrowerName'=>$this->borrowerName,
            'loanedAt'=>$this->loanedAt, 'dueDate'=>$this->dueDate, 'returnedAt'=>$this->returnedAt, 'isReturned'=>$this->isReturned()];
    }

    public static function fromArray(array $data): self
    {
        $loan = new self((string)$data['id'], (string)$data['bookId'], (string)$data['borrowerName'],
            (string)$data['loanedAt'], (string)$data['dueDate'], isset($data['returnedAt']) ? (string)$data['returnedAt'] : null);
        return $loan;
    }
}
