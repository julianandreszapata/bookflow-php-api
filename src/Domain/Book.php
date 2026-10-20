<?php

declare(strict_types=1);
namespace BookFlow\Domain;

final class Book
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $author,
        public readonly string $isbn,
        public readonly int $totalCopies,
        public int $availableCopies,
        public readonly string $createdAt
    ) {
        if (trim($title) === '' || trim($author) === '' || trim($isbn) === '') {
            throw new BusinessRuleException('Title, author and ISBN are required.');
        }
        if ($totalCopies < 1) throw new BusinessRuleException('Total copies must be at least 1.');
        if ($availableCopies < 0 || $availableCopies > $totalCopies) throw new BusinessRuleException('Available copies are outside the valid range.');
    }

    public static function register(string $title, string $author, string $isbn, int $totalCopies): self
    {
        return new self(self::newId(), trim($title), trim($author), trim($isbn), $totalCopies, $totalCopies, gmdate(DATE_ATOM));
    }

    public function checkoutCopy(): void
    {
        if ($this->availableCopies <= 0) throw new BusinessRuleException('No copies are currently available.');
        $this->availableCopies--;
    }

    public function returnCopy(): void
    {
        if ($this->availableCopies >= $this->totalCopies) throw new BusinessRuleException('Returned copies cannot exceed the inventory.');
        $this->availableCopies++;
    }

    public function toArray(): array
    {
        return ['id'=>$this->id, 'title'=>$this->title, 'author'=>$this->author, 'isbn'=>$this->isbn,
            'totalCopies'=>$this->totalCopies, 'availableCopies'=>$this->availableCopies, 'createdAt'=>$this->createdAt];
    }

    public static function fromArray(array $data): self
    {
        return new self((string)$data['id'], (string)$data['title'], (string)$data['author'], (string)$data['isbn'],
            (int)$data['totalCopies'], (int)$data['availableCopies'], (string)$data['createdAt']);
    }

    private static function newId(): string { return bin2hex(random_bytes(16)); }
}
