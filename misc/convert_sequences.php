<?php

class Conn extends \Pdo
{
    public function exec($statement)
    {
        echo $statement . ";\r\n";
        return parent::exec($statement); // TODO: Change the autogenerated stub
    }

    public function query($statement)
    {
        echo $statement . "; \r\n";
        return parent::query($statement); // TODO: Change the autogenerated stub
    }

}

$pdo = new Conn('pgsql:host=127.0.0.1;port=5432;dbname=m2_core', 'm2_core', '123123qa');

$getIdentitiesSql = "
SELECT *
FROM pg_indexes

where schemaname not in ('pg_catalog')
and indexname like '%_primary'
and indexdef not like '%, %'
";
$res = $pdo->query($getIdentitiesSql);
$lines = $res->fetchAll(\PDO::FETCH_ASSOC);

//$matches = [];

$seqs = [];
foreach ($lines as $line) {

    preg_match('#\.([^ ]*).*\(([^\)]*id)\)#', $line['indexdef'], $matches);

    if (isset($matches[1])) {
        $seqs [] = ['table' => $matches[1], 'col' => $matches[2], 'schema' => $line['schemaname']];
    }
}
$seqs [] = ['table' => 'shipping_tablerate', 'col' => 'pk', 'schema' => $seqs[0]['schema']];
$seqs [] = ['table' => 'quote_id_mask', 'col' => 'entity_id', 'schema' => $seqs[0]['schema']];

foreach ($seqs as $seq) {
    $maxValSQL = "select max({$seq['col']}) as mx, count(1) as cnt from {$seq['table']} ";
    $r1 = $pdo->query($maxValSQL);
    $r = $r1->fetchAll(\PDO::FETCH_ASSOC);
    $mx = 0;
    $cnt = 0;
    if (count($r)) {
        $mx = $r[0]['mx'];
        $cnt = $r[0]['cnt'];
    }
    $createSql = "create sequence {$seq['table']}_seq ";
    if ($mx) {
        $mx++;
        $createSql .= " start with {$mx}";
    }

    $r1 = $pdo->query($createSql);
    $r1 = $pdo->query("alter table {$seq['table']} alter column {$seq['col']}
            set default nextval('{$seq['schema']}.{$seq['table']}_seq')");
    $r1 = $pdo->query("alter sequence {$seq['table']}_seq owned by {$seq['table']}.{$seq['col']}");
}


$res = $pdo->query("select * from pg_catalog.pg_tables where tablename like 'sequence_%';");

foreach ($res->fetchAll(\PDO::FETCH_ASSOC) as $seq) {
    $maxValSQL = "select max(sequence_value) as mx from {$seq['tablename']};";
    $r1 = $pdo->query($maxValSQL);
    $r = $r1->fetchAll(\PDO::FETCH_ASSOC);
    $mx = 0;
    if (count($r)) {
        $mx = $r[0]['mx'];
    }

    $createSql = "create sequence {$seq['tablename']} ";
    if ($mx) {
        $mx++;
        $createSql .= " start with {$mx}";
    }
    $r0 = $pdo->query("DROP TABLE {$seq['schemaname']}.{$seq['tablename']};");
    $r1 = $pdo->query($createSql);

}

