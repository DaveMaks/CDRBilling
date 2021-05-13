<?php


use Phalcon\Di\Injectable;
use Phalcon\Exception;
use Phalcon\Mvc\Model;


class LDAP
{
    static private $link;
    var $base_dn;
    var $config = array(
        'domainName' => 'example.local'
    );

    /**
     * @param string $ldap_server сервер AD  "ldaps://adserver.ad.com"
     * @param string $ldap_user пользователь для доступа к каталогу ldap "CN=web service account,OU=Service Accounts,DC=ad,DC=com";
     * @param string $ldap_pass
     * @param array $conf
     * @throws Exception
     * @example $ldap=new LDAP("ldaps://adserver.ad.com","CN=web service account,OU=Service Accounts,DC=ad,DC=com","password")
     */
    function __construct(string $ldap_server, string $ldap_user, string $ldap_pass, $conf = array())
    {
        self::$link = ldap_connect($ldap_server);
        ldap_set_option(self::$link, LDAP_OPT_PROTOCOL_VERSION, 3);
        if (!empty($conf))
            $this->config = array_merge($this->config, $conf);
        if (!@ldap_bind(self::$link, $ldap_user . '@' . $this->config['domainName'], $ldap_pass))
            throw new \Exception(ldap_error(self::$link), ldap_errno(self::$link));
        //$this->base_dn = $base_dn;
    }

    /**
     *
     * @param string $filter фильтр запроса ldap
     * @param array $attributes какие поля выбирать
     * @return array
     */
    public function Search($filter, $attributes = array(), $oneLevel = false)
    {
        if ($oneLevel)
            $result = ldap_list(self::$link, $this->config['baseDN'], $filter, $attributes);
        else
            $result = ldap_search(self::$link, $this->config['baseDN'], $filter, $attributes);
        $info = ldap_get_entries(self::$link, $result);
        return $info;
    }


    /**
     *
     * @param $DN string по какому пути выбирать
     * @param $filter string дополнительный фильтр (&(objectCategory=organizationalUnit) $filter)
     * @param $attributes array Какие поля выбирать
     * @return  array|null
     */
    public function getOUFromDN($DN, $filter = '', $attributes = array('name', 'ou', 'description', 'objectguid'))
    {

        $result = ldap_list(self::$link, $DN, '(&(objectClass=' . OBJECTCLASS_OU . ')' . $filter . ')', $attributes);
        $info = ldap_get_entries(self::$link, $result);
        ldap_free_result($result);
        if ($info['count'] == 0)
            return null;
        /**Сводим в нормальному списку, в зависимости от указанных атрибутов*/
        return $this->FormatArray($info, $attributes);
    }

    public function getUsersFromDN($DN, $filter = '', $attributes = array('name', 'ou', 'description', 'objectguid'))
    {
        if (!isset($attributes['samaccountname'])) array_push($attributes, 'samaccountname');
        $result = ldap_list(self::$link, $DN, '(&(objectClass=' . OBJECTCLASS_USER . ')' . $filter . ')', $attributes);
        $info = ldap_get_entries(self::$link, $result);
        ldap_free_result($result);
        if ($info['count'] == 0)
            return null;
        /**Сводим в нормальному списку, в зависимости от указанных атрибутов*/
        return $this->FormatArray($info, $attributes);

    }

    private function FormatArray(&$arrayFromAD, &$attributes)
    {
        $answer = array();
        for ($row = 0; $row < (int)$arrayFromAD['count']; $row++) {
            $tmp = array();
            foreach ($attributes as $column) {
                if (isset($arrayFromAD[$row][$column])) {
                    unset($arrayFromAD[$row][$column]['count']);
                    $tmp[$column] = implode(';', $arrayFromAD[$row][$column]);
                } else
                    $tmp[$column] = '';
            }
            $answer[] = $tmp;
        }
        return $answer;
    }

    /**
     */
    public function ImportToDB($unit = true, $user = true, $truncate = false)
    {
        if ($truncate) {

        }
        $this->ImportUnitsFromDn($this->config['baseDN'], 0, true);
    }

    /**
     * Рекурсивный обход дерева OU и добавления в БД
     * @param $DN string DN путь до OU
     * @param $id_parent int ID родителя 0-root
     * @param $addUser bool также добовлять пользователей в OU
     *
     * @return bool
     * @throws Exception
     *
     */
    private function ImportUnitsFromDn($DN, $id_parent = 0, $addUser = false)
    {
        $lstUnit = $this->getOUFromDN($DN, $this->config['filterOU'], array('name', 'description', 'distinguishedname', 'objectguid'));
        if (empty($lstUnit))
            return;
        foreach ($lstUnit as $item) {
            $Unit = tableUnits::findFirstByOverdata(bin2hex($item['objectguid']));
            if (!$Unit)
                $Unit = new tableUnits();
            $Unit->id_parent = $id_parent;
            $Unit->Name = $item['name'];
            $Unit->Description = $item['description'];
            $Unit->overdata = bin2hex($item['objectguid']);
            if ($Unit->save()) {
                if ($addUser)
                    $this->ImportUserFromDn($item['distinguishedname'], $Unit->id);
                $this->ImportUnitsFromDn($item['distinguishedname'], $Unit->id, $addUser);
            } else {
                throw new Exception('Возникла ошибка при импорте OU (' . implode(';', $item) . ')');
            }
        }
        return true;
    }

    /**
     * Рекурсивный обход дерева OU и добавления в БД
     * @param $DN string DN путь до OU
     * @param $id_parent int ID родителя 0-root
     *
     * @return bool
     * @throws Exception
     *
     */
    private function ImportUserFromDn($DN, $id_parent = 0)
    {
        $lstUnit = $this->getUsersFromDN($DN,
            $this->config['filterUser'],
            array('displayname', 'description', $this->config['ipPhone'], 'title', 'department', 'mail', 'objectguid'));
        if (empty($lstUnit))
            return;
        foreach ($lstUnit as $item) {
            $User = tableUsers::findFirstByOverdata(bin2hex($item['objectguid']));
            if (!$User)
                $User = new tableUsers();
            $User->id_unit = $id_parent;
            $User->cn = $item['samaccountname'];
            $User->fullName = $item['displayname'];
            $User->description = $item['description'];
            $User->tel = $item[$this->config['ipPhone']];
            $User->title = $item['title'];
            $User->department = $item['department'];
            $User->email = $item['mail'];
            $User->overdata = bin2hex($item['objectguid']);
            if (!$User->save())
                throw new Exception('Возникла ошибка при импорте User (' . implode(';', $item) . ')');
        }
        return true;
    }

    function __destruct()
    {
        ldap_unbind(self::$link);
    }

    /**
     * Преобразует длинныей DN в простой список первых CN
     * @param $dnArr [] массив DN
     * @return Массив групп
     */
    static function memberof2Array($dnArr)
    {
        $ret=array();
        if (empty($dnArr))
            return null;
        foreach ($dnArr as $dn) {
            if (preg_match('/^CN=([^,]+)/', $dn, $matches))
                $ret[]=$matches[1];
        }
        return $ret;
    }
}