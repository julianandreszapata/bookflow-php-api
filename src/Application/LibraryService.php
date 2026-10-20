<?php

declare(strict_types=1);
namespace BookFlow\Application;

use BookFlow\Domain\Book;
use BookFlow\Domain\Loan;
use BookFlow\Domain\BusinessRuleException;

final class LibraryService
{
    public function __construct(private readonly LibraryRepository $repository) {}

    public function listBooks(): array { return array_map(static fn(Book $book) => $book->toArray(), $this->repository->allBooks()); }

    public function listLoans(): array
    {
        return array_map(function (Loan $loan): array {
            $data = $loan->toArray();
            $data['bookTitle'] = $this->repository->findBook($loan->bookId)?->title ?? 'Unknown book';
            return $data;
        }, $this->repository->allLoans());
    }

    public function createBook(array $input): array
    {
        $title = trim((string)($input['title'] ?? ''));
        $author = trim((string)($input['author'] ?? ''));
        $isbn = trim((string)($input['isbn'] ?? ''));
        $copies = filter_var($input['totalCopies'] ?? null, FILTER_VALIDATE_INT);
        if ($copies === false || $copies === null) throw new BusinessRuleException('totalCopies must be an integer.');
        if ($this->repository->findBookByIsbn($isbn) !== null) throw new BusinessRuleException('A book with this ISBN already exists.');
        $book = Book::register($title, $author, $isbn, $copies);
        $this->repository->saveBook($book);
        return $book->toArray();
    }

    public function createLoan(array $input): array
    {
        $bookId = trim((string)($input['bookId'] ?? ''));
        $borrower = trim((string)($input['borrowerName'] ?? ''));
        $dueDate = trim((string)($input['dueDate'] ?? ''));
        $book = $this->repository->findBook($bookId) ?? throw new \OutOfBoundsException('Book was not found.');
        $loan = Loan::create($bookId, $borrower, $dueDate);
        $book->checkoutCopy();
        $this->repository->saveBook($book);
        $this->repository->saveLoan($loan);
        $data = $loan->toArray();
        $data['bookTitle'] = $book->title;
        return $data;
    }

    public function returnLoan(string $id): array
    {
        $loan = $this->repository->findLoan($id) ?? throw new \OutOfBoundsException('Loan was not found.');
        $book = $this->repository->findBook($loan->bookId) ?? throw new \OutOfBoundsException('Book linked to loan was not found.');
        $loan->markReturned();
        $book->returnCopy();
        $this->repository->saveBook($book);
        $this->repository->saveLoan($loan);
        $data = $loan->toArray();
        $data['bookTitle'] = $book->title;
        return $data;
    }
}
