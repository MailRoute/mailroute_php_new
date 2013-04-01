<?php
namespace MailRoute\API\Tests;

use Jamm\Tester\ClassTest;
use MailRoute\API\Entity\ContactReseller;
use MailRoute\API\Entity\Customer;
use MailRoute\API\Entity\Domain;
use MailRoute\API\Entity\Reseller;
use MailRoute\API\Exception;
use MailRoute\API\IActiveEntity;
use MailRoute\API\IClient;
use MailRoute\API\NotFoundException;

class TestClient extends ClassTest
{
	/** @var ClientMock */
	private $Client;

	public function __construct(IClient $Client)
	{
		$this->Client = $Client;
		$this->Client->setDeleteNotFoundIsError(true);
		$this->skipAllExceptLast();
		$this->skipTest('testDomainGetActivePolicy');
	}

	public function testGetRootSchema()
	{
		$result = $this->Client->GET();
		$this->assertIsArray($result);
		$this->assertTrue(isset($result['reseller']));
	}

	public function testSchema()
	{
		$result = $this->Client->GET('reseller/schema');
		$this->assertIsArray($result);
		$this->assertTrue(isset($result['allowed_detail_http_methods']));
	}

	public function testResellerList()
	{
		$reseller_name = 'test '.microtime(1);
		/** @var IActiveEntity[] $resellers */
		$resellers   = array();
		$NewReseller = new Reseller($this->Client);
		$NewReseller->setName($reseller_name.'1');
		$resellers[] = $this->Client->API()->Reseller()->create($NewReseller);
		$resellers[] = $this->Client->API()->Reseller()->create(array('name' => $reseller_name.'2'));
		$resellers[] = $this->Client->API()->Reseller()->create(array('name' => $reseller_name.'3'));
		$resellers[] = $this->Client->API()->Reseller()->create(array('name' => $reseller_name.'4'));
		$resellers[] = $this->Client->API()->Reseller()->create(array('name' => $reseller_name.'5'));
		$resellers[] = $this->Client->API()->Reseller()->create(array('name' => $reseller_name.'6'));
		$result      = $this->Client->API()->Reseller()->offset(1)->limit(5)->fetchList();
		$this->assertIsArray($result);
		$this->assertTrue(count($result)==5)->addCommentary(print_r($result, 1));
		foreach ($resellers as $Reseller)
		{
			$Reseller->delete();
		}
	}

	public function testResellerPOST()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1000, 9999);
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$this->assertTrue(is_object($Reseller));
		$this->assertEquals($Reseller->getName(), $reseller_name);
		$result = $this->Client->API()->Reseller()->filter(array('name' => $reseller_name))->fetchList();
		$this->assertIsArray($result);
		$Reseller->delete();
	}

	public function testContactResellerPOST()
	{
		$email = 'test@example.com';
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => 'test contactReseller '.microtime(1).mt_rand(1000, 9999)));
		/** @var ContactReseller $ContactReseller */
		$Item = new ContactReseller($this->Client);
		$Item->setEmail($email);
		$Item->setReseller($Reseller->getResourceUri());
		$ContactReseller = $this->Client->API()->ContactReseller()->create($Item);
		$this->assertTrue(is_object($ContactReseller));
		$this->assertEquals($ContactReseller->getEmail(), $email);
		$ContactReseller->delete();
	}

	public function testResellerDELETE()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1000, 9999).'_del';
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$this->assertTrue(is_object($Reseller));
		$this->assertEquals($Reseller->getName(), $reseller_name)->addCommentary(print_r($Reseller, 1));
		$result = $Reseller->delete();
		$this->assertTrue($result)->addCommentary(gettype($Reseller).': '.print_r($Reseller, 1));
		try
		{
			$result = $this->Client->API()->Reseller()->get($Reseller->getId());
		}
		catch (Exception $E)
		{
			$result = false;
		}
		$this->assertTrue(!$result)->addCommentary(gettype($result).': '.print_r($result, 1));
	}

	public function testResellerPUT()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1000, 9999).'_put';
		/** @var Reseller $Reseller */
		$NewReseller = new Reseller($this->Client);
		$NewReseller->setName($reseller_name);
		$Reseller = $this->Client->API()->Reseller()->create($NewReseller);
		$this->assertEquals($Reseller->getName(), $reseller_name);
		$Reseller->setName($reseller_name.'_updated');
		try
		{
			$Reseller = $this->Client->API()->Reseller()->update($Reseller);
		}
		catch (\Exception $E)
		{
			$this->assertTrue(false);
		}
		$this->assertEquals($Reseller->getName(), $reseller_name.'_updated', true);
		$Reseller->setName($reseller_name.'_saved');
		try
		{
			$Reseller->save();
		}
		catch (\Exception $E)
		{
			$this->assertTrue(false);
		}
		$Reseller = $this->Client->API()->Reseller()->get($Reseller->getId());
		$this->assertEquals($Reseller->getName(), $reseller_name.'_saved', true);
		$Reseller->delete();
	}

	public function testSearch()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1000, 9999);
		/** @var IActiveEntity[] $resellers */
		$resellers   = array();
		$NewReseller = new Reseller($this->Client);
		$NewReseller->setName($reseller_name.'1');
		$resellers[] = $this->Client->API()->Reseller()->create($NewReseller);
		$NewReseller->setName($reseller_name.'2');
		$resellers[] = $this->Client->API()->Reseller()->create($NewReseller);
		$result      = $this->Client->API()->Reseller()->search($reseller_name);
		$this->assertIsArray($result);
		$this->assertIsObject($result[0]);
		foreach ($resellers as $Reseller)
		{
			$Reseller->delete();
		}
	}

	public function testResellerCreateAndDeleteAdmin()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1000, 9999).'create_admin';
		$NewReseller   = new Reseller($this->Client);
		$NewReseller->setName($reseller_name);
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create($NewReseller);
		try
		{
			$Admin = $Reseller->createAdmin('test@example.com', 1);
		}
		catch (Exception $E)
		{
			$this->assertTrue(false)->addCommentary(print_r($E->getResponse(), 1));
			return false;
		}
		$this->assertIsObject($Admin);
		$this->assertEquals($Admin->getEmail(), 'test@example.com');
		$this->assertTrue($Reseller->deleteAdmin('test@example.com'));
		$this->assertTrue($Reseller->delete());
	}

	public function testResellerGetContacts()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1000, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		for ($i = 0; $i < 5; $i++)
		{
			$this->Client->API()->ContactReseller()->create(array(
				'reseller' => $this->Client->getAPIPathPrefix().$Reseller->getApiEntityResource().'/'.$Reseller->getId().'/',
				'email'    => 'reseller_contact@example.com'
			));
		}
		$Contacts = $Reseller->getContacts();
		$this->assertIsArray($Contacts);
		$this->assertIsObject($Contacts[0]);
		foreach ($Contacts as $Contact)
		{
			$this->assertTrue($Contact->delete());
		}
		$this->assertTrue($Reseller->delete());
	}

	public function testResellerCreateContact()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1000, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$result   = $Reseller->createContact('contact@example.com');
		$this->assertIsObject($result);
		$this->assertEquals($result->getEmail(), 'contact@example.com');
		$this->assertTrue($result->delete());
		$this->assertTrue($Reseller->delete());
	}

	public function testResellerCreateCustomer()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1000, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$result   = $Reseller->createCustomer('customer'.$reseller_name);
		$this->assertIsObject($result);
		$this->assertEquals($result->getName(), 'customer'.$reseller_name);
		$this->assertTrue($result->delete());
		$this->assertTrue($Reseller->delete());
	}

	public function testCustomerCreateContact()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer = $Reseller->createCustomer('customer'.$reseller_name);
		$result   = $Customer->createContact('customer@example.com');
		$this->assertIsObject($result);
		$this->assertEquals($result->getEmail(), 'customer@example.com');
		$this->assertTrue($result->delete());
		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());
	}

	public function testCustomerCreateAdmin()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer = $Reseller->createCustomer('customer'.$reseller_name);
		$result   = $Customer->createAdmin('admin_customer@example.com');
		$this->assertIsObject($result);
		$this->assertEquals($result->getEmail(), 'admin_customer@example.com');
		$this->assertTrue($result->delete());
		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());
	}

	public function testCustomerDeleteAdmin()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller  = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer  = $Reseller->createCustomer('customer'.$reseller_name);
		$adm_email = 'admin_customer'.md5($reseller_name).'@example.com';
		$Customer->createAdmin($adm_email);
		$Customer->createAdmin('2'.$adm_email);
		$result = $Customer->deleteAdmin($adm_email);
		$this->assertTrueStrict($result);
		/** @var Customer $RefreshCustomer */
		$RefreshCustomer = $this->Client->API()->Customer()->get($Customer->getId());
		$new_list        = $RefreshCustomer->getAdmins();
		$this->assertEquals(count($new_list), 1, true);
		$this->assertEquals($new_list[0]->getEmail(), '2'.$adm_email);
		$this->assertTrue($new_list[0]->delete());
		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());
	}

	public function testCustomerCreateDomain()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer = $Reseller->createCustomer('customer'.$reseller_name);
		$d        = 'domain'.md5(microtime(1).mt_rand(1, 9999).mt_rand(1, 9999).__LINE__).'.name';
		$result   = $Customer->createDomain($d);
		$this->assertIsObject($result);
		$this->assertEquals($result->getName(), $d);
		$this->assertTrue($result->delete());
		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());
	}

	public function testDomainMoveToCustomer()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller  = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer1 = $Reseller->createCustomer('customer1'.$reseller_name);
		$Customer2 = $Reseller->createCustomer('customer2'.$reseller_name);
		$Domain    = $Customer1->createDomain('domain'.md5(microtime(1).mt_rand(1, 9999).__LINE__).'.name');
		$this->assertEquals($Domain->getCustomer(), $Customer1->getResourceUri());
		$this->assertEquals($Domain->getCustomer(), $Customer1->getResourceUri());
		$result = $Domain->moveToCustomer($Customer2);
		$this->assertTrueStrict($result);
		/** @var Domain $FreshDomain */
		$FreshDomain = $this->Client->API()->Domain()->get($Domain->getId());
		$this->assertEquals($FreshDomain->getCustomer(), $Customer2->getResourceUri());
		$Domain->delete();
		$Customer2->delete();
		$Customer1->delete();
		$Reseller->delete();
	}

	public function testDomainCreateContact()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer = $Reseller->createCustomer('customer'.$reseller_name);
		$Domain   = $Customer->createDomain('domain'.md5(microtime(1).mt_rand(1, 9999).__LINE__).'.name');

		$email  = 'domain.contact.'.md5($Domain->getResourceUri()).'@example.com';
		$result = $Domain->createContact($email);
		$this->assertIsObject($result);
		$this->assertEquals($result->getEmail(), $email);
		$this->assertTrue($result->delete());
		$this->assertTrue($Domain->delete());
		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());
	}

	public function testDomainCreateMailServer()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer = $Reseller->createCustomer('customer'.$reseller_name);
		$Domain   = $Customer->createDomain('domain'.md5(microtime(1).mt_rand(1, 9999).__LINE__).'.name');

		$result = $Domain->createMailServer('127.0.0.1');
		$this->assertIsObject($result);
		$this->assertEquals($result->getServer(), '127.0.0.1');
		$this->assertTrue($result->delete());
		$this->assertTrue($Domain->delete());
		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());
	}

	public function testDomainCreateOutboundServer()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer = $Reseller->createCustomer('customer'.$reseller_name);
		$Domain   = $Customer->createDomain('domain'.md5(microtime(1).mt_rand(1, 9999).__LINE__).'.name');

		$result = $Domain->createOutboundServer('127.0.0.1');
		$this->assertIsObject($result);
		$this->assertEquals($result->getServer(), '127.0.0.1');
		$this->assertTrue($result->delete());
		$this->assertTrue($Domain->delete());
		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());
	}

	public function testDomainCreateEmailAccount()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer = $Reseller->createCustomer('customer'.$reseller_name);
		$Domain   = $Customer->createDomain('domain'.md5(microtime(1).mt_rand(1, 9999).__LINE__).'.name');

		$lp     = substr(md5(microtime(1).mt_rand(1, 9999).__LINE__), 5);
		$result = $Domain->createEmailAccount($lp);
		$this->assertIsObject($result);
		$this->assertEquals($result->getLocalpart(), $lp);
		$this->assertTrue($result->delete());
		$this->assertTrue($Domain->delete());
		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());
	}

	public function testDomainBulkCreateEmailAccount()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer = $Reseller->createCustomer('customer'.$reseller_name);
		$Domain   = $Customer->createDomain('domain'.md5(microtime(1).mt_rand(1, 9999).__LINE__).'.name');

		$localparts = array();
		for ($i = 0; $i < 5; $i++)
		{
			$localparts[] = array('localpart' => $i);
		}
		$result = $Domain->bulkCreateEmailAccount($localparts);
		$this->assertIsObject($result[0]);
		$this->assertIsObject($result[0]);
		foreach ($result as $key => $EmailAccount)
		{
			if (is_a($EmailAccount, 'MailRoute\\API\\Exception'))
			{
				/** @var Exception $EmailAccount */
				$this->assertTrue(false)->addCommentary('Exception: '.print_r($EmailAccount->getResponse(), 1));
				continue;
			}
			$this->assertEquals($EmailAccount->getLocalpart(), $key);
			$this->assertTrue($EmailAccount->delete());
		}
		$this->assertTrue($Domain->delete());
		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());
	}

	public function testDomainCreateAlias()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer = $Reseller->createCustomer('customer'.$reseller_name);
		$Domain   = $Customer->createDomain('domain'.md5(microtime(1).mt_rand(1, 9999).__LINE__).'.name');

		$name   = 'domain'.md5(microtime(1).mt_rand(1, 9999).__LINE__).'.name';
		$result = $Domain->createAlias($name);
		$this->assertIsObject($result);
		$this->assertEquals($result->getName(), $name);

		$this->assertTrue($result->delete());
		$this->assertTrue($Domain->delete());
		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());

	}

	public function testDomainAddToBlackList()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer = $Reseller->createCustomer('customer'.$reseller_name);
		$Domain   = $Customer->createDomain('domain'.md5(microtime(1).mt_rand(1, 9999).__LINE__).'.name');

		$email  = substr(md5(microtime(1).mt_rand(1, 9999).__LINE__), 5).'@example.com';
		$result = $Domain->addToBlackList($email);
		$this->assertIsObject($result);
		$this->assertTrue($result->delete());
		$this->assertTrue($Domain->delete());
		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());
	}

	public function testDomainAddToWhiteList()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer = $Reseller->createCustomer('customer'.$reseller_name);
		$Domain   = $Customer->createDomain('domain'.md5(microtime(1).mt_rand(1, 9999).__LINE__).'.name');

		$email  = substr(md5(microtime(1).mt_rand(1, 9999).__LINE__), 5).'@example.com';
		$result = $Domain->addToWhiteList($email);
		$this->assertIsObject($result);
		$this->assertTrue($result->delete());
		$this->assertTrue($Domain->delete());
		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());
	}

	public function testEmailAccountAddAlias()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller     = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer     = $Reseller->createCustomer('customer'.$reseller_name);
		$Domain       = $Customer->createDomain('domain'.md5(microtime(1).mt_rand(1, 9999).__LINE__).'.name');
		$localpart    = substr(md5(microtime(1).mt_rand(1, 9999).__LINE__), 5);
		$EmailAccount = $Domain->createEmailAccount($localpart);
		$result       = $EmailAccount->addAlias($localpart.'alias');
		$this->assertIsObject($result);
		$this->assertEquals($result->getLocalpart(), $localpart.'alias');
		$this->assertEquals($result->getEmailAccount(), $EmailAccount->getResourceUri());
		$this->assertTrue($result->delete());
		$this->assertTrue($EmailAccount->delete());
		$this->assertTrue($Domain->delete());
		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());
	}

	public function testEmailAccountBulkAddAlias()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller     = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer     = $Reseller->createCustomer('customer'.$reseller_name);
		$Domain       = $Customer->createDomain('domain'.md5(microtime(1).mt_rand(1, 9999).__LINE__).'.name');
		$localpart    = substr(md5(microtime(1).mt_rand(1, 9999).__LINE__), 5);
		$EmailAccount = $Domain->createEmailAccount($localpart);
		$aliases      = array();
		for ($i = 0; $i < 3; $i++)
		{
			$aliases[] = $localpart.'alias'.$i;
		}
		$result = $EmailAccount->bulkAddAlias($aliases);
		$this->assertIsArray($result);
		$this->assertIsObject($result[0]);
		foreach ($result as $key => $LocalpartAlias)
		{
			if (is_a($LocalpartAlias, 'MailRoute\\API\\Exception'))
			{
				/** @var Exception $LocalpartAlias */
				$this->assertTrue(false)->addCommentary('Exception: '.print_r($LocalpartAlias->getResponse(), 1));
				continue;
			}
			$this->assertEquals($LocalpartAlias->getLocalpart(), $localpart.'alias'.$key);
			$this->assertEquals($LocalpartAlias->getEmailAccount(), $EmailAccount->getResourceUri());
			$this->assertTrue($LocalpartAlias->delete());
		}
		$this->assertTrue($EmailAccount->delete());
		$this->assertTrue($Domain->delete());
		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());
	}

	public function testEmailAccountAddToBlackList()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller     = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer     = $Reseller->createCustomer('customer'.$reseller_name);
		$Domain       = $Customer->createDomain('domain'.md5(microtime(1).mt_rand(1, 9999).__LINE__).'.name');
		$localpart    = substr(md5(microtime(1).mt_rand(1, 9999).__LINE__), 5);
		$EmailAccount = $Domain->createEmailAccount($localpart);

		$blacklisted_email = $localpart.'@example.com';
		$result            = $EmailAccount->addToBlackList($blacklisted_email);
		$this->assertIsObject($result);
		$this->assertEquals($result->getEmail(), $blacklisted_email);
		$this->assertEquals($result->getEmailAccount(), $EmailAccount->getResourceUri());
		$this->assertTrue($result->delete());
		$this->assertTrue($EmailAccount->delete());
		$this->assertTrue($Domain->delete());
		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());
	}

	public function testEmailAccountAddToWhiteList()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller     = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer     = $Reseller->createCustomer('customer'.$reseller_name);
		$Domain       = $Customer->createDomain('domain'.md5(microtime(1).mt_rand(1, 9999).__LINE__).'.name');
		$localpart    = substr(md5(microtime(1).mt_rand(1, 9999).__LINE__), 5);
		$EmailAccount = $Domain->createEmailAccount($localpart);

		$whitelisted_email = $localpart.'@example.com';
		$result            = $EmailAccount->addToWhiteList($whitelisted_email);
		$this->assertIsObject($result);
		$this->assertEquals($result->getEmail(), $whitelisted_email);
		$this->assertEquals($result->getEmailAccount(), $EmailAccount->getResourceUri());
		$this->assertTrue($result->delete());
		$this->assertTrue($EmailAccount->delete());
		$this->assertTrue($Domain->delete());
		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());
	}

//	public function testPolicyDomainGetDefaultPolicy()
//	{
//		$PolicyDomains = $this->Client->API()->PolicyDomain()->fetchList();
//		print_r($PolicyDomains);
//	}

	public function testEmailAccountMakeAliasesFrom()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller     = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer     = $Reseller->createCustomer('customer'.$reseller_name);
		$Domain       = $Customer->createDomain('domain'.md5(microtime(1).mt_rand(1, 9999).__LINE__).'.name');
		$localpart    = substr(md5(microtime(1).mt_rand(1, 9999).__LINE__), 5);
		$EmailAccount = $Domain->createEmailAccount($localpart);

		$to_aliases = array();
		foreach (range(1, 5) as $i)
		{
			$to_aliases[] = $Domain->createEmailAccount($localpart.$i);
		}

		try
		{
			$result = $EmailAccount->makeAliasesFrom($to_aliases);
			$this->assertTrue($result);
		}
		catch (Exception $E)
		{
			$this->assertTrue(false)->addCommentary($E->getResponse());
		}

		$this->assertTrue($EmailAccount->delete());
		$this->assertTrue($Domain->delete());
		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());
	}

	public function testResellerDeleteContact()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1000, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$email    = 'contact@example.com';
		$Contact  = $Reseller->createContact($email);
		try
		{
			$result = $Reseller->deleteContact($email);
		}
		catch (Exception $E)
		{
			$this->assertTrue(false)->addCommentary(print_r($E->getResponse(), 1));
			$Reseller->delete();
			return false;
		}
		$this->assertEquals($result, 1);
		try
		{
			$this->Client->API()->ContactReseller()->get($Contact->getId());
			$this->assertTrue(false)->addCommentary('was not deleted');
		}
		catch (NotFoundException $Exception)
		{
			$this->assertTrue(true);
		}
		$this->assertTrue($Reseller->delete());
	}

	public function testCustomerDeleteContact()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer = $Reseller->createCustomer('customer'.$reseller_name);
		$email    = 'customer@example.com';
		$Contact  = $Customer->createContact($email);
		$result   = $Customer->deleteContact($email);
		try
		{
			$this->Client->API()->ContactCustomer()->get($Contact->getId());
			$this->assertTrue(false)->addCommentary('was not deleted');
		}
		catch (NotFoundException $Exception)
		{
			$this->assertTrue(true);
		}
		$this->assertEquals($result, 1);

		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());
	}

	public function testDomainDeleteContact()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer = $Reseller->createCustomer('customer'.$reseller_name);
		$Domain   = $Customer->createDomain('domain'.md5(microtime(1).mt_rand(1, 9999).__LINE__).'.name');
		$email    = 'domain.contact.'.md5($Domain->getResourceUri()).'@example.com';
		$Contact  = $Domain->createContact($email);
		$result   = $Domain->deleteContact($email);
		$this->assertEquals($result, 1);
		try
		{
			$this->Client->API()->ContactDomain()->get($Contact->getId());
			$this->assertTrue(false)->addCommentary('was not deleted');
		}
		catch (NotFoundException $E)
		{
			$this->assertTrue(true);
		}
		$this->assertTrue($Domain->delete());
		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());
	}

	public function testDomainGetActivePolicy()
	{
		//TODO
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer = $Reseller->createCustomer('customer'.$reseller_name);
		$Domain   = $Customer->createDomain('domain'.md5(microtime(1).mt_rand(1, 9999).__LINE__).'.name');

		$result = $Domain->getActivePolicy();
		$this->assertIsObject($result);

		$this->assertTrue($Domain->delete());
		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());
	}

	public function testEmailAccountRegenerateApiKey()
	{
		$reseller_name = 'test '.microtime(1).mt_rand(1, 9999).__FUNCTION__;
		/** @var Reseller $Reseller */
		$Reseller     = $this->Client->API()->Reseller()->create(array('name' => $reseller_name));
		$Customer     = $Reseller->createCustomer('customer'.$reseller_name);
		$Domain       = $Customer->createDomain('domain'.md5(microtime(1).mt_rand(1, 9999).__LINE__).'.name');
		$localpart    = substr(md5(microtime(1).mt_rand(1, 9999).__LINE__), 5);
		$EmailAccount = $Domain->createEmailAccount($localpart);

		$result = $EmailAccount->regenerateApiKey();

		$this->assertTrue($result!==false)->addCommentary(print_r($result, 1));
		$this->assertTrue($EmailAccount->delete());
		$this->assertTrue($Domain->delete());
		$this->assertTrue($Customer->delete());
		$this->assertTrue($Reseller->delete());

	}

}
