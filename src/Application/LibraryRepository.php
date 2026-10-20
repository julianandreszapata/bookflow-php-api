<?php

declare(strict_types=1);
namespace BookFlow\Application;

use BookFlow\Domain\Book;
use BookFlow\Domain\Loan;

interface LibraryRepository
{
    /** @return list<Book> */ public function allBooks(): array;
    public function findBook(string $id): ?Book;
    public function findBookByIsbn(string $isbn): ?Book;
    public function saveBook(Book $book): void;
    /** @return list<Loan> */ public function allLoans(): array;
    public function findLoan(string $id): ?Loan;
    public function saveLoan(Loan $loan): void;
}
