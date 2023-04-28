<?php


namespace App\Model\Services;

use Nette;
use Nette\Database\Context;
use Nette\Security\IAuthenticator;
use Nette\Security\Passwords;

/**
 * Users management.
 */
final class UserManager implements IAuthenticator
{
    use Nette\SmartObject;
    const USER_ADMIN = 1, USER_MEMBER = 2, USER_CUSTOMER = 3, B2B_CUSTOMER = 4;
    CONST B2B_REQUEST = 1,
        B2B_APPROVED = 2;
    public const
        TABLE_NAME = 'user',
        COLUMN_ID = 'id',
        COLUMN_NAME = 'email',
        COLUMN_PASSWORD_HASH = 'password',
        OLD_HASH = 'oldpassword',
        OLD_SALT = 'oldsalt',
        COLUMN_EMAIL = 'email',
        COLUMN_ROLE = 'role';
    /** @var Context */
    private $database;
    /** @var Passwords */
    private $passwords;

    public function __construct(Context $database, Passwords $passwords)
    {
        $this->database = $database;
        $this->passwords = $passwords;
    }

    /**
     * Performs an authentication.
     * @throws Nette\Security\AuthenticationException
     */
    public function authenticate(array $credentials): Nette\Security\IIdentity
    {
        [$username, $password] = $credentials;
        $row = $this->database->table(self::TABLE_NAME)
            ->where(self::COLUMN_NAME, $username)
            ->where('active', 1)
            ->fetch();
        if (!$row) {
            throw new Nette\Security\AuthenticationException('The username is incorrect.', self::IDENTITY_NOT_FOUND);
        }
        if($row[self::COLUMN_PASSWORD_HASH] === null) {
            if($row[self::OLD_HASH] === $this->ocHash($row, $password)) {
                $newHash = $this->passwords->hash($password);
                $this->database->table(self::TABLE_NAME)->where('id', $row['id'])->update([self::COLUMN_PASSWORD_HASH => $newHash]);
                $row = $this->database->table(self::TABLE_NAME)
                    ->where(self::COLUMN_NAME, $username)
                    ->fetch();
            } else {
                throw new Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);
            }
        }
        if (!$this->passwords->verify($password, $row[self::COLUMN_PASSWORD_HASH])) {
            throw new Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);
        } elseif ($this->passwords->needsRehash($row[self::COLUMN_PASSWORD_HASH])) {
            $row->update([
                self::COLUMN_PASSWORD_HASH => $this->passwords->hash($password),
            ]);
        }
        $arr = $row->toArray();
        unset($arr[self::COLUMN_PASSWORD_HASH]);
        return new Nette\Security\Identity($row[self::COLUMN_ID], $row[self::COLUMN_ROLE], $arr);
    }

    /**
     * Adds new user.
     * @throws DuplicateNameException
     */
    public function add(string $username, string $email, string $password): void
    {
        Nette\Utils\Validators::assert($email, 'email');
        try {
            $this->database->table(self::TABLE_NAME)->insert([
                self::COLUMN_PASSWORD_HASH => $this->passwords->hash($password),
                self::COLUMN_EMAIL => $email
            ]);
        } catch (Nette\Database\UniqueConstraintViolationException $e) {
            throw new DuplicateNameException;
        }
    }

    private function ocHash($row, $password)
    {
        return sha1($row[self::OLD_SALT].sha1($row[self::OLD_SALT].sha1($this->ocEscape($password))));
    }

    private function ocEscape($value) {
        return str_replace(array("\\", "\0", "\n", "\r", "\x1a", "'", '"'), array("\\\\", "\\0", "\\n", "\\r", "\Z", "\'", '\"'), $value);
    }
}

class DuplicateNameException extends \Exception
{
}
