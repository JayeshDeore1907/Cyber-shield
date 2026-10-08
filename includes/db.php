<?php
$config = require __DIR__ . '/../config/config.php';

class CSDB {
    private ?PDO $pdo = null;
    private string $jsonFile;
    public bool $jsonMode = false;

    public function __construct(string $sqlitePath) {
        $this->jsonFile = dirname($sqlitePath) . '/reports.json';
        $dir = dirname($sqlitePath);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        if (class_exists('PDO') && in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->pdo = new PDO('sqlite:' . $sqlitePath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->exec('PRAGMA foreign_keys = ON');
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS reports (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                reference_no TEXT UNIQUE NOT NULL,
                fraud_type TEXT NOT NULL,
                platform TEXT NOT NULL,
                incident_date TEXT NOT NULL,
                amount REAL DEFAULT 0,
                description TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'Received',
                created_at TEXT NOT NULL
            )");
        } else {
            $this->jsonMode = true;
            if (!file_exists($this->jsonFile)) file_put_contents($this->jsonFile, json_encode([], JSON_PRETTY_PRINT));
        }
    }
    private function read(): array { $d=@file_get_contents($this->jsonFile); $a=json_decode($d?:'[]',true); return is_array($a)?$a:[]; }
    private function write(array $rows): void { file_put_contents($this->jsonFile, json_encode(array_values($rows), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX); }
    public function query(string $sql) {
        if (!$this->jsonMode) return $this->pdo->query($sql);
        $rows=$this->read();
        if (stripos($sql,'SELECT * FROM reports')===0) {
            usort($rows,fn($a,$b)=>($b['id']??0)<=>($a['id']??0)); return new CSResult($rows);
        }
        if (preg_match("/SELECT COUNT\(\*\) FROM reports WHERE fraud_type IN \(([^)]*)\)/i",$sql,$m)) {
            $vals=[]; preg_match_all("/'([^']+)'/",$m[1],$vals); $set=$vals[1]??[]; $n=count(array_filter($rows,fn($r)=>in_array($r['fraud_type'],$set,true))); return new CSResult([[$n]]);
        }
        if (stripos($sql,'SELECT COUNT(*) FROM reports')===0) return new CSResult([[count($rows)]]);
        return new CSResult([]);
    }
    public function prepare(string $sql) {
        if (!$this->jsonMode) return $this->pdo->prepare($sql);
        return new CSStatement($this,$sql);
    }
    public function insert(array $data): void { $rows=$this->read(); $next=1; foreach($rows as $r) $next=max($next,(int)($r['id']??0)+1); 
        $data['id']=$next; $data = ['id'=>$next,'reference_no'=>$data['reference_no']??'','fraud_type'=>$data['fraud_type']??'','platform'=>$data['platform']??'','incident_date'=>$data['incident_date']??'','amount'=>$data['amount']??0,'description'=>$data['description']??'','status'=>$data['status']??'Received','created_at'=>$data['created_at']??'']; $rows[]=$data; $this->write($rows); }
    public function updateStatus(int $id,string $status): void { $rows=$this->read(); foreach($rows as &$r) if((int)$r['id']===$id) $r['status']=$status; $this->write($rows); }
    public function findByReference(string $ref): ?array { foreach($this->read() as $r) if(($r['reference_no']??'')===$ref) return $r; return null; }
}
class CSResult {
    private array $rows; public function __construct(array $rows){$this->rows=$rows;}
    public function fetchAll(int $mode=PDO::FETCH_ASSOC): array { return $this->rows; }
    public function fetchColumn(): mixed { $r=$this->rows[0]??[]; return is_array($r)?array_values($r)[0]??null:null; }
}
class CSStatement {
    private CSDB $db; private string $sql; private array $data=[]; private $result=null;
    public function __construct(CSDB $db,string $sql){$this->db=$db;$this->sql=$sql;}
    public function execute(array $params=[]): bool {
        $this->data=$params;
        if (stripos($this->sql,'INSERT INTO reports')===0) {
            [$ref,$type,$platform,$date,$amount,$desc,$status,$created]=$params;
            $this->db->insert(['reference_no'=>$ref,'fraud_type'=>$type,'platform'=>$platform,'incident_date'=>$date,'amount'=>$amount,'description'=>$desc,'status'=>$status,'created_at'=>$created]);
            return true;
        }
        if (stripos($this->sql,'UPDATE reports SET status')===0) { $this->db->updateStatus((int)($params[1]??0),(string)($params[0]??'Received')); return true; }
        if (stripos($this->sql,'SELECT * FROM reports WHERE reference_no')===0) { $this->result=$this->db->findByReference((string)($params[0]??'')); return true; }
        return true;
    }
    private function dbRows(): array { $r=$this->db->query('SELECT * FROM reports'); return $r->fetchAll(); }
    public function fetch(int $mode=PDO::FETCH_ASSOC): array|false { return $this->result ?: false; }
}

$db = new CSDB($config['db_path']);
if ($db->jsonMode) {
    // Compatibility layer: wrap insert mapping for the standalone build.
    $origInsert = new ReflectionClass($db); // no-op: forces class autoload only
}
return $db;
