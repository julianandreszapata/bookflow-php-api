<?php

declare(strict_types=1);
namespace BookFlow\Infrastructure;

use BookFlow\Application\LibraryRepository;
use BookFlow\Domain\Book;
use BookFlow\Domain\Loan;

final class JsonFileLibraryRepository implements LibraryRepository
{
    public function __construct(private readonly string $filePath)
    {
        $directory = dirname($this->filePath);
        if (!is_dir($directory)) mkdir($directory, 0775, true);
        if (!is_file($this->filePath)) $this->writeState($this->seedState());
    }

    public function allBooks(): array
    {
        $state = $this->readState();
        $books = array_map(static fn(array $row) => Book::fromArray($row), $state['books']);
        usort($books, static fn(Book $a, Book $b) => strcasecmp($a->title, $b->title));
        return $books;
    }

    public function findBook(string $id): ?Book
    {
        foreach ($this->allBooks() as $book) if ($book->id === $id) return $book;
        return null;
    }

    public function findBookByIsbn(string $isbn): ?Book
    {
        foreach ($this->allBooks() as $book) if (strcasecmp($book->isbn, $isbn) === 0) return $book;
        return null;
    }

    public function saveBook(Book $book): void
    {
        $state = $this->readState();
        $state['books'][$book->id] = $book->toArray();
        $this->writeState($state);
    }

    public function allLoans(): array
    {
        $state = $this->readState();
        $loans = array_map(static fn(array $row) => Loan::fromArray($row), array_values($state['loans']));
        usort($loans, static fn(Loan $a, Loan $b) => strcmp($b->loanedAt, $a->loanedAt));
        return $loans;
    }

    public function findLoan(string $id): ?Loan
    {
        foreach ($this->allLoans() as $loan) if ($loan->id === $id) return $loan;
        return null;
    }

    public function saveLoan(Loan $loan): void
    {
        $state = $this->readState();
        $state['loans'][$loan->id] = $loan->toArray();
        $this->writeState($state);
    }

    private function readState(): array
    {
        $handle = fopen($this->filePath, 'c+');
        if ($handle === false) throw new \RuntimeException('Unable to open local storage file.');
        try {
            flock($handle, LOCK_SH);
            $json = stream_get_contents($handle);
            $data = json_decode($json ?: '', true);
            return is_array($data) && isset($data['books'], $data['loans']) ? $data : ['books' => [], 'loans' => []];
        } finally { flock($handle, LOCK_UN); fclose($handle); }
    }

    private function writeState(array $state): void
    {
        $handle = fopen($this->filePath, 'c+');
        if ($handle === false) throw new \RuntimeException('Unable to open local storage file.');
        try {
            flock($handle, LOCK_EX);
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            fflush($handle);
        } finally { flock($handle, LOCK_UN); fclose($handle); }
    }

    private function seedState(): array
    {
        $books = [];
        foreach ([
            ['Clean Code', 'Robert C. Martin', '9780132350884', 3],
            ['The Pragmatic Programmer', 'David Thomas and Andrew Hunt', '9780135957059', 2],
            ['Domain-Driven Design', 'Eric Evans', '9780321125217', 1],
        ] as [$title, $author, $isbn, $copies]) {
            $book = Book::register($title, $author, $isbn, $copies);
            $books[$book->id] = $book->toArray();
        }
        return ['books' => $books, 'loans' => []];
    }
}
