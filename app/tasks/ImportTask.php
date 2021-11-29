<?php
use Phalcon\Cli\Task;
use Phalcon\Exception;
use App\LocalClass\Files;
use Phalcon\Logger;
use Phalcon\Logger\Adapter\Stream;
use Phalcon\Mvc\Model\Transaction\Manager as TxManager;
use App\CDR\Import as ImportDB;

class ImportTask extends Task
{
    var $logger;

    public function initialize()
    {
        $adapter = new Stream($this->config->logger->name);
        $this->logger = new Logger(
            'messages',
            [
                'main' => $adapter,
            ]
        );
    }

    public function mainAction()
    {
        echo '--------------------------------' . "\n\r";
        echo '   remoteCMR - Удалить лишний мусор *.CMR' . "\n\r";
        echo '   cdr [folder] [format] - Импорт cdr файлов, по умолчанию данные берутся из конфига, можно указать доп. параметры' . "\n\r";
        echo '                           folder - папка где искать файлы' . "\n\r";
        echo '                           format - регулярное выражение для выборки фалов' . "\n\r";
        echo '   ActiveDirectory-импорт и синхранизация справочника пользователя из AD' . "\n\r";
        echo '--------------------------------' . "\n\r";

    }

    /**
     *  Удалить лишний мусор
     */
    public function remoteCMRAction()
    {
        if (!is_dir($this->config->importcdr->folder))
            return;
        array_map('unlink', glob($this->config->importcdr->folder . 'cmr_*'));
    }

    public function cdrAction(string $folder = "", string $format = "")
    {
        try {
            if (empty($folder))
                $folder = $this->config->importcdr->folder;
            if (empty($format))
                $format = $this->config->importcdr->mask;
            if (!is_dir($folder))
                throw new Exception('Папка для импорта не найдена');
            $listFile = $this->getListFiles($folder, $format);
            if (count($listFile) == 0)
                return;
            $import = new ImportDB();
            foreach ($listFile as $f) {
                if ($import->Load($f->fullname)) {
                    // переносить в папку архива
                    if ($this->config->importcdr->moveToArchive)
                        rename($f->fullname, $this->config->importcdr->folderArchive . $f->name);
                    else
                        unlink($f->fullname);
                }
            }

        } catch (\Exception $ex) {
            echo $ex->getMessage() . "\n\r";
        }
    }

    /**
     * @param $cdrPath string путь до папки где находядтся логи
     * @param $mask string
     * @return Files[] спискок файлов удовлетворяющий маске
     * @throws \Exception
     *
     */
    private function getListFiles($cdrPath, $mask): ?array
    {
        $files = scandir($cdrPath);
        if (empty($files))
            throw new Exception('Нет файлов для импорта');
        $_listFile = array();
        foreach ($files as $f) {
            if (preg_match('/' . $mask . '/', $f))
                $_listFile[] = new Files($f, $cdrPath);
        }
        return $_listFile;
    }

    /**
     * Парсинг cdr файлов в папке
     */
    public function parsingcdrAction($status = false)
    {
        try {
            $i = 0;
            if (!is_dir($this->config->importcdr->folder))
                throw new Exception('Папка для импорта не найдена');
            $arrayListFile = $this->GetListFiles($this->config->importcdr->folder);
            if (empty($arrayListFile))
                return;
            $cntFile = count($arrayListFile);
            foreach ($arrayListFile as $file) {
                $this->ImportFile($file);
                echo round((100 / $cntFile) * $i, 1) . '% ' . $file . " - OK\n";
                $i++;
            }

        } catch (Exception $ex) {
            echo $ex->getMessage() . "\n";
            $this->logger->error($ex->getMessage());
        }
    }

    /** Синхранизация справочника с AD
     */
    public function ActiveDirectoryAction()
    {
        try {
            $dc1 = new LDAP(
                $this->config->ActiveDirectory->host,
                $this->config->ActiveDirectory->user,
                $this->config->ActiveDirectory->password,
                $this->config->ActiveDirectory->toArray()
            );
            $dc1->ImportToDB(true, true);
            //$listOU=$dc1->Search("(&(objectCategory=organizationalUnit)(name=*))",array('name','ou','description'),true);
            //$this->SaveTreeADUnits($dc1, 0,$this->config->ActiveDirectory->baseDN);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }

    public function ActiveDirectoryTestAction()
    {
        try {
            $dc1 = new LDAP(
                $this->config->ActiveDirectory->host,
                $this->config->ActiveDirectory->user,
                $this->config->ActiveDirectory->password,
                $this->config->ActiveDirectory->toArray()
            );
            $this->CreateTreeUserFormAD($dc1, $this->config->ActiveDirectory->baseDN);


            ///var_dump($dc1);
            //$dc1->ImportToDB(true, true);
            // $listOU=$dc1->Search("(&(objectCategory=organizationalUnit)(name=*))",
            //  array('name','ou','description'),true);

            /*$lstWorker = $dc1->getUsersFromDN($this->config->ActiveDirectory->baseDN,
                $this->config->ActiveDirectory->filterUser,
                array('displayname', 'description', $this->config->ActiveDirectory->ipPhone, 'title', 'department', 'mail', 'objectguid'));

            var_dump($lstWorker);*/
            //$this->SaveTreeADUnits($dc1, 0,$this->config->ActiveDirectory->baseDN);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
    }

    public function CreateTreeUserFormAD(&$dc1, $DN)
    {
        static $i = 0;
        $i++;
        $lstUnit = $dc1->getOUFromDN($DN,
            $dc1->config['filterOU'],
            array('name', 'description', 'distinguishedname', 'objectguid'));
        if (empty($lstUnit)) {
            $i--;
            return;
        }
        foreach ($lstUnit as $item) {
            echo str_repeat("--", $i) . $item["name"] . "    \t" . $item["description"] . "\n";
            $listWorker = $lstUnit = $dc1->getUsersFromDN($item['distinguishedname'],
                $dc1->config['filterUser'],
                array('displayname', 'description', $dc1->config['ipPhone'], 'title', 'department', 'mail', 'objectguid'));
            $this->CreateTreeUserFormAD($dc1, $item['distinguishedname']);
            echo str_repeat("  ", $i) . "samaccountname" . " \t" .
                "displayname" . "      \t" .
                $dc1->config['ipPhone'] . "   \t" .
                "description" . "   \t" .
                "title" . "   \t" .
                "department" . "   \t". "\n";
            if (empty($listWorker))
                continue;
            foreach ($listWorker as $Workeritem) {
                echo str_repeat("  ", $i) . $Workeritem["samaccountname"] . " \t" .
                    $Workeritem["displayname"] . "      \t" .
                    $Workeritem[$dc1->config['ipPhone']] . "   \t" .
                    $Workeritem["description"] . "   \t" .
                    $Workeritem["title"] . "   \t" .
                    $Workeritem["department"] . "   \t"
                    . "\n";
                //var_dump($listWorker);
            }

            //var_dump($item);
        }
        $i--;
    }


}
