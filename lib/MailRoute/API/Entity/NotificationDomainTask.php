<?php
namespace MailRoute\API\Entity;

class NotificationDomainTask extends \MailRoute\API\ActiveEntity
{
	protected $api_entity_resource = 'notification_domain_task';
	protected $fields = array('domain', 'enabled', 'id', 'priority', 'resource_uri');

	public function getDomain()
	{
		return $this->fields['domain'];
	}

	public function getEnabled()
	{
		return $this->fields['enabled'];
	}

	public function setEnabled($enabled)
	{
		$this->fields['enabled'] = $enabled;
	}

	public function getId()
	{
		return $this->fields['id'];
	}

	public function setId($id)
	{
		$this->fields['id'] = $id;
	}

	public function getPriority()
	{
		return $this->fields['priority'];
	}

	public function setPriority($priority)
	{
		$this->fields['priority'] = $priority;
	}

	public function getResourceUri()
	{
		return $this->fields['resource_uri'];
	}

}
