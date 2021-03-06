<?php
namespace MailRoute\API\Entity;

use MailRoute\API\ActiveEntity;
use MailRoute\API\NotFoundException;
use MailRoute\API\ValidationException;

class Reseller extends ActiveEntity
{
	protected $api_entity_resource = 'reseller';
	protected $fields = array();
	/** @var Admins[] */
	private $customers;

	/**
	 * @param $email
	 * @param bool $send_welcome
	 * @param string $username
	 * @throws \MailRoute\API\ValidationException
	 * @return Admins
	 */
	public function createAdmin($email, $send_welcome = false, $username = '')
	{
		if (!$this->getId())
		{
			throw new ValidationException('This method requires ID');
		}
		$Client = $this->getAPIClient();
		$Admin  = new Admins($Client);
		$Admin->setEmail($email);
		$Admin->setUsername($username);
		$Admin->setSendWelcome($send_welcome);
		$Admin->setReseller($this->getResourceUri());
		$new_data = $Client->callAPI($Admin->getApiEntityResource().'/'.$this->getApiEntityResource().'/'.$this->getId().'/',
			'POST', $Admin->getAPIEntityFields());
		$Admin->setAPIEntityFields($new_data);
		return $Admin;
	}

	public function deleteAdmin($email)
	{
		if (!$this->getId())
		{
			throw new ValidationException('This method requires ID');
		}
		$Admins = $this->getAdmins();
		foreach ($Admins as $Admin)
		{
			if ($Admin->getEmail()==$email)
			{
				return $Admin->delete();
			}
		}
		return true;
	}

	/**
	 * @param string $email
	 * @return ContactReseller
	 */
	public function createContact($email)
	{
		$Contact = new ContactReseller($this->getAPIClient());
		$Contact->setReseller($this->getResourceUri());
		$Contact->setEmail($email);
		return $this->getAPIClient()->API()->ContactReseller()->create($Contact);
	}

	public function deleteContact($email)
	{
		/** @var ContactReseller[] $Contacts */
		$Contacts = $this->getAPIClient()->API()->ContactReseller()->
				filter(array('email' => $email, 'reseller' => $this->getId()))->fetchList();
		if (empty($Contacts))
		{
			return true;
		}
		$deleted = 0;
		foreach ($Contacts as $Contact)
		{
			$deleted += $Contact->delete();
		}
		return $deleted;
	}

	/**
	 * @param string $name
	 * @return Customer
	 */
	public function createCustomer($name)
	{
		$Customer = new Customer($this->Client);
		$Customer->setReseller($this->getResourceUri());
		$Customer->setName($name);
		return $this->getAPIClient()->API()->Customer()->create($Customer);
	}

	public function getAllowBranding()
	{
		return $this->fields['allow_branding'];
	}

	public function setAllowBranding($allow_branding)
	{
		$this->fields['allow_branding'] = $allow_branding;
	}

	public function getAllowCustomerBranding()
	{
		return $this->fields['allow_customer_branding'];
	}

	public function setAllowCustomerBranding($allow_customer_branding)
	{
		$this->fields['allow_customer_branding'] = $allow_customer_branding;
	}

	public function getBrandingInfo()
	{
		return parent::getBrandingInfo();
	}

	/**
	 * @return ContactReseller[]
	 */
	public function getContacts()
	{
		return parent::getEntityContacts(new ContactReseller($this->getAPIClient()));
	}

	public function getCreatedAt()
	{
		return $this->fields['created_at'];
	}

	/**
	 * @return Customer[]
	 */
	public function getCustomers()
	{
		if (empty($this->customers))
		{
			try
			{
				$this->customers = $this->getAPIClient()->API()->Customer()->filter(array('reseller' => $this->getId()))->fetchList();
			}
			catch (NotFoundException $Exception)
			{
				return array();
			}
		}
		return $this->customers;
	}

	public function getId()
	{
		return $this->fields['id'];
	}

	public function setId($id)
	{
		$this->fields['id'] = $id;
	}

	public function getName()
	{
		return $this->fields['name'];
	}

	public function setName($name)
	{
		$this->fields['name'] = $name;
	}

	public function getUpdatedAt()
	{
		return $this->fields['updated_at'];
	}

	public function getAdmins()
	{
		return parent::getAdmins();
	}
}
