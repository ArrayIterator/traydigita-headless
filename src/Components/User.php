<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use DateTimeInterface;
use SensitiveParameter;
use Throwable;
use TrayDigita\WP\Headless\Resource\Attributes\SensitiveData;
use TrayDigita\WP\Headless\Resource\Lib\DatetimeImmutableUnit;
use WP_User;
use function absint;
use function get_current_user_id;
use function is_int;
use function is_numeric;
use function is_string;
use function strtolower;
use function trim;
use function user_can;
use function wp_check_password;

/**
 * Property back compatibility for WP_User
 *
 * @property string $nickname
 * @property string $description
 * @property string $user_description
 * @property string $first_name
 * @property string $user_firstname
 * @property string $last_name
 * @property string $user_lastname
 * @property string $user_login
 * @property string $user_pass
 * @property string $user_nicename
 * @property string $user_email
 * @property string $user_url
 * @property string $user_registered
 * @property string $user_activation_key
 * @property string $user_status
 * @property int $user_level
 * @property string $display_name
 * @property string $spam
 * @property string $deleted
 * @property string $locale
 * @property string $rich_editing
 * @property string $syntax_highlighting
 * @property string $use_ssl
 */
#[SensitiveData('User contain email & password hash')]
final class User
{
    /**
     * @var WP_User $user WordPress user object
     */
    #[SensitiveData('This property contain sensitive information about user')]
    public readonly WP_User $user;

    /**
     * @var int $id User ID
     */
    public readonly int $id;

    /**
     * @var string $username User login name (lowercase)
     */
    public readonly string $username;

    /**
     * @var string $password User password hash
     */
    #[SensitiveData('This property contain sensitive information about user')]
    public readonly string $password;

    /**
     * @var string $nickname User nickname
     */
    public readonly string $nickname;

    /**
     * @var string $email User email address
     */
    public readonly string $email;

    /**
     * @var string $url User website URL
     */
    public readonly string $url;

    /**
     * @var ?DatetimeImmutableUnit $registered User registration date and time
     */
    public readonly ?DatetimeImmutableUnit $registered;

    /**
     * @var string $activationKey User activation key
     */
    #[SensitiveData('This property contain sensitive information about authentication')]
    public readonly string $activationKey;

    /**
     * @var string $displayName User display name
     */
    public readonly string $displayName;

    /**
     * @var bool $exists Whether the user exists in the database
     */
    public readonly bool $exists;

    /**
     * @param WP_User|null $user WordPress user object
     */
    public function __construct(
        #[SensitiveParameter]
        #[SensitiveData('This parameter contain sensitive information about user')]
        ?WP_User $user = null
    ) {
        if ($user === null) {
            $user = self::findUser(get_current_user_id());
        }
        if (!$user instanceof WP_User) {
            $user = new WP_User(0);
        }
        $this->id = $this->makeInt($user->ID);
        $this->username = strtolower($this->makeString($user->user_login));
        $this->password = $this->makeString($user->user_pass);
        $this->nickname = $this->makeString($user->nickname);
        $this->email = $this->makeString($user->user_email);
        $this->url = $this->makeString($user->user_url);
        $this->registered = $this->makeDatetime($user->user_registered);
        $this->activationKey = $this->makeString($user->user_activation_key);
        $this->displayName = $this->makeString($user->display_name);
        $this->exists = $user->exists() && $this->id > 0;
        $this->user = $user;
    }

    /**
     * Check if the user has a specific capability
     *
     * @param string $cap Capability to check
     * @return bool True if the user has the capability, false otherwise
     */
    public function can(string $cap): bool
    {
        if (!$this->exists) {
            return false;
        }
        return user_can($this->user, $cap);
    }

    /**
     * Find a WP_User object by user ID or username
     *
     * @param mixed $user User ID or username
     * @return ?WP_User Returns a WP_User object if found, null otherwise
     */
    #[SensitiveData('This method returns sensitive data of user')]
    public static function findUser(
        #[SensitiveParameter]
        #[SensitiveData('This parameter contain sensitive information about user')]
        mixed $user
    ): ?WP_User {
        if ($user instanceof WP_User) {
            return $user;
        }
        if (!is_int($user) && is_numeric($user)) {
            $user = (int)$user;
        }
        if (is_int($user)) {
            $user = absint($user);
            if ($user > 0) {
                $userdata = WP_User::get_data_by('id', $user);
                $wpUser = new WP_User();
                $wpUser->init($userdata);
                return $wpUser;
            }
            return null;
        }
        if (is_string($user)) {
            $user = strtolower(trim($user));
            if (!$user) {
                return null;
            }
            $userdata = WP_User::get_data_by('id', $user);
            $wpUser = new WP_User();
            $wpUser->init($userdata);
            return $wpUser;
        }
        return null;
    }

    /**
     * Create a User instance from a WP_User object or user ID
     *
     * @param mixed $user WP_User object or user ID
     * @return ?self Returns a User instance or null if the user is invalid
     */
    #[SensitiveData('This method returns sensitive data of user')]
    public function find(
        #[SensitiveParameter]
        #[SensitiveData('This parameter contain sensitive information about user')]
        mixed $user
    ): ?self {
        $user = self::findUser($user);
        if ($user instanceof WP_User) {
            if ($user->ID === $this->id) {
                return $this;
            }
            return new self($user);
        }
        return null;
    }

    /**
     * Create a User instance from a WP_User object or user ID
     *
     * @param mixed $user WP_User object or user ID
     * @return ?self Returns a User instance or null if the user is invalid
     */
    #[SensitiveData('This method returns sensitive data of user')]
    public function findOrEmpty(
        #[SensitiveParameter]
        #[SensitiveData('This parameter contain sensitive information about user')]
        mixed $user
    ): ?self {
        $user = self::findUser($user);
        if ($user instanceof WP_User) {
            if ($user->ID === $this->id) {
                return $this;
            }
            return new self($user);
        }
        if ($this->id === 0) {
            return $this;
        }
        return new self(null);
    }

    /**
     * Convert a value to an integer
     *
     * @param mixed $value The value to convert
     * @return int The converted integer value, or 0 if the value is not numeric
     */
    private function makeInt(mixed $value): int
    {
        if (is_numeric($value)) {
            return absint($value);
        }
        return 0;
    }

    /**
     * Convert a value to a string
     *
     * @param mixed $value The value to convert
     * @return string The converted string value, or an empty string if the value is not a string
     */
    #[SensitiveData('This method converts a value to a string')]
    private function makeString(
        #[SensitiveParameter]
        #[SensitiveData('This parameter contain sensitive information about user')]
        mixed $value
    ): string {
        if (is_string($value)) {
            return trim($value);
        }
        return '';
    }

    /**
     * Convert a value to a DatetimeImmutableStringable instance
     *
     * @param mixed $value The value to convert
     * @return ?DatetimeImmutableUnit
     * The converted DatetimeImmutableStringable instance, or null if the value is invalid
     */
    private function makeDatetime(mixed $value): ?DatetimeImmutableUnit
    {
        if ($value instanceof DatetimeImmutableUnit) {
            return $value;
        }
        if ($value instanceof DateTimeInterface) {
            return DatetimeImmutableUnit::createFromInterface($value);
        }
        if (is_string($value)) {
            try {
                return DatetimeImmutableUnit::createFromWordPressDatabase($value);
            } catch (Throwable) {
                return null;
            }
        }
        return null;
    }

    /**
     * Check if the provided password matches the user's password
     *
     * @param string $password The password to check
     * @return bool True if the password matches, false otherwise
     */
    #[SensitiveData('This method checks if the provided password matches the user\'s password')]
    public function isPasswordMatch(#[SensitiveParameter] string $password): bool
    {
        return wp_check_password($password, $this->user->user_pass);
    }

    /**
     * Magic method to access properties of the underlying WP_User object
     *
     * @param string $name The name of the property to access
     * @return mixed The value of the property, or null if the property does not exist
     */
    public function __get(string $name)
    {
        return $this->user->$name ?? null;
    }

    #[SensitiveData('This method prevents setting properties on the User object')]
    public function __set(
        string $name,
        #[SensitiveParameter]
        #[SensitiveData('This parameter contain sensitive information about user')]
        mixed $value
    ): void {
        // Prevent setting properties on the User object
    }

    public function __isset(string $name): bool
    {
        return isset($this->user->$name);
    }
}
