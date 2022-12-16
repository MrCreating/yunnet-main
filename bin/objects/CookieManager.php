<?php

namespace unt\objects;

/**
 * Cookie manager
 * Balances, bited cookies...
*/

class CookieManager extends BaseObject
{
	private User $currentUser;
	private bool $isValid;

	private int $cookies  = 0;
	private int $bCookies = 0;

	public function __construct (User $user, int $cookiesCount, int $biteCookiesCount)
	{
        parent::__construct();

		if (!$user->valid()) return;
			
		$this->currentUser = $user;
		$this->isValid     = true;

		$this->cookies  = $cookiesCount;
		$this->bCookies = $biteCookiesCount;
	}

	public function getCookiesCount (): int
	{
		return $this->cookies;
	}

	public function getBiteCookiesCount (): int
	{
		return $this->bCookies;
	}

	public function valid (): bool
	{
		return $this->isValid;
	}

    /**
     * Pays cookie for another user
     *
     * @param int $user_id - to id (another user)
     * @param int $amount - integer, sum of cookies
     * @param string $comment - optional
     *
     * Error codes
     * -1 - incorrect amount
     * -2 - user not exists
     * -3 - not enough cookies
     * -4 - you have been blacklisted by this user
     * @return int 1 if ok or int with error code
     *
     * Parameters:
     */
	public function payTo (int $user_id, int $amount = 1, string $comment = ''): int
	{
		// amount limits
		if ($amount <= 0 || $amount > 100000000) return -1;

		$entity = User::findById($user_id);
		if (!$entity) return -2;

		if ($entity->isBanned() || $entity->isBlocked() || $entity->inBlacklist()) return -4;

		if ($amount > $this->getCookiesCount()) return -3;

		$my_new_balance = $this->getCookiesCount() - $amount;
		$pn_new_balance = $entity->getSettings()->getSettingsGroup(Settings::ACCOUNT_GROUP)->getBalance()->getCookiesCount() + $amount;

		if ($this->currentConnection->prepare("UPDATE users.info SET cookies = ? WHERE id = ? AND is_deleted = 0 LIMIT 1")->execute([$my_new_balance, $this->currentUser->getId()]))
		{
			if ($this->currentConnection->prepare("UPDATE users.info SET cookies = ? WHERE id = ? AND is_deleted = 0 LIMIT 1")->execute([$pn_new_balance, $entity->getId()]))
			{
				return 1;
			}
		}

		return 0;
	}
}

?>