<?php
namespace MailRoute\API;

class EntityHandler
{
	private $entity_name;
	private $Client;

	public function __construct(IClient $Client)
	{
		$this->Client = $Client;
	}

	public function __call($function, $arguments)
	{
		if (empty($this->entity_name))
		{
			$this->entity_name = $function;
			return $this;
		}
		$method           = $function;
		$url_request_part = '/'.$this->entity_name.'/';
		$this->handleMethod($arguments, $method, $url_request_part);
		return $this->Client->callAPI($url_request_part, $method, $arguments);
	}

	/**
	 * @param $arguments
	 * @param $method
	 * @param $url_request_part
	 */
	protected function handleMethod(&$arguments, &$method, &$url_request_part)
	{
		if ($method=='GET' && !empty($arguments))
		{
			$id = array_shift($arguments);
			if (!empty($id))
			{
				$url_request_part .= $id.'/';
			}
			if (empty($arguments)) return;
			$filters = array_shift($arguments);
			if (!empty($arguments))
			{
				$offset = array_shift($arguments);
			}
			if (!empty($arguments))
			{
				$limit = array_shift($arguments);
			}
			$arguments = $filters;
			if (!empty($offset))
			{
				$arguments['offset'] = $offset;
			}
			if (!empty($limit))
			{
				$arguments['limit'] = $limit;
			}
		}
	}

	public function setEntityName($entity_name)
	{
		$this->entity_name = $entity_name;
	}
}
