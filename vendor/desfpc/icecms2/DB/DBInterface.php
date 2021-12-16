<?php
declare(strict_types=1);
/**
 * iceCMS2 v0.1a
 * Created by Sergey Peshalov https://github.com/desfpc
 * https://github.com/desfpc/iceCMS2
 *
 * DB Interface
 */

namespace iceCMS2\DB;

interface DBInterface
{
    /**
     * Get error flag
     *
     * @return bool
     */
    public function getError(): bool;

    /**
     * Get error text
     *
     * @return string|null
     */
    public function getErrorText(): ?string;

    /**
     * Get warning flag
     *
     * @return bool
     */
    public function getWarning(): bool;

    /**
     * Get warning text
     *
     * @return string|null
     */
    public function getWarningText(): ?string;

    /**
     * Get connecting flag
     *
     * @return bool
     */
    public function getConnected(): bool;

    /**
     * Get connecting status
     *
     * @return string|null
     */
    public function getConnectedText(): ?string;

    /**
     * Connect to DB
     *
     * @return bool
     */
    public function connect(): bool;

    /**
     * Close DB connection
     *
     * @return bool
     */
    public function disconnect(): bool;

    /**
     * Query to DB
     *
     * @param string $query SQL query
     * @param bool $isFree clear result after query
     * @param bool $isCnt return number of rows, not rows array
     * @param bool $isForced try to execute the request even if there are errors
     * @return bool|array<mixed, mixed>
     */
    public function query(string $query, $isFree = true, $isCnt = false, $isForced = false): bool|array;

    /**
     * MultiQuery to DB
     *
     * @param string $query
     * @return bool
     */
    public function multiQuery(string $query): bool;
}