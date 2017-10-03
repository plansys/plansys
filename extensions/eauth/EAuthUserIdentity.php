<?php
/**
 * EAuthUserIdentity class file.
 *
 * @author Maxim Zemskov <nodge@yandex.ru>
 * @link http://github.com/Nodge/yii-eauth/
 * @license http://www.opensource.org/licenses/bsd-license.php
 */

/**
 * EAuthUserIdentity is a base User Identity class to authenticate with EAuth.
 *
 * @package application.extensions.eauth
 */
class EAuthUserIdentity extends CUserIdentity {

	const ERROR_NOT_AUTHENTICATED = 3;

	/**
	 * @var EAuthServiceBase the authorization service instance.
	 */
	protected $service;

	/**
	 * @var string the unique identifier for the identity.
	 */
	private $id;

	/**
	 * @var string the display name for the identity.
	 */
	protected $name;

	/**
	 * Constructor.
	 *
	 * @param EAuthServiceBase $service the authorization service instance.
	 */
	public function __construct($service) {
		$this->service = $service;
	}

	/**
	 * Authenticates a user based on {@link service}.
	 * This method is required by {@link IUserIdentity}.
	 *
	 * @return boolean whether authentication succeeds.
	 */
	 
	 
	public function authenticate() {
		if ($this->service->isAuthenticated) {
			$attributes = $this->service->getAttributes();
			
			$account = new GOauth();
			
			$account->gid = $attributes['id'];
			$account->name = $attributes['name'];
			$account->email = $attributes['email'];
			
			$account->regUser();
			
			$this->id = $account->id;
			$this->name = $this->service->getAttribute('name');
			
			$this->setState('id', $acc_id);
			$this->setState('gid', $attributes['id']);
			$this->setState('name', $this->name);
			$this->setState('service', $this->service->serviceName);
			
			
			$this->setState('fullRole', $account->fullRole);
            $this->setState('role', $account->role);
            $this->setState('roleId', $account->roleId);
  

			$this->errorCode = self::ERROR_NONE;
		}
		else {
			$this->errorCode = self::ERROR_NOT_AUTHENTICATED;
		}
		return !$this->errorCode;
	}

	/**
	 * Returns the unique identifier for the identity.
	 * This method is required by {@link IUserIdentity}.
	 *
	 * @return string the unique identifier for the identity.
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Returns the display name for the identity.
	 * This method is required by {@link IUserIdentity}.
	 *
	 * @return string the display name for the identity.
	 */
	public function getName() {
		return $this->name;
	}
}
