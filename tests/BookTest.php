<?php

declare(strict_types=1);

use BookFlow\Domain\Book;
use BookFlow\Domain\BusinessRuleException;
use PHPUnit\Framework\TestCase;

final class BookTest extends TestCase
{
    public function testCheckoutDecreasesAvailableCopies(): void
    {
        $book = Book::register('Clean Code', 'Robert C. Martin', 'isbn-test-1', 2);
        $book->checkoutCopy();
        self::assertSame(1, $book->availableCopies);
    }

    public function testCheckoutFailsWhenOutOfStock(): void
    {
        $book = Book::register('Clean Code', 'Robert C. Martin', 'isbn-test-2', 1);
        $book->checkoutCopy();
        $this->expectException(BusinessRuleException::class);
        $book->checkoutCopy();
    }
}
