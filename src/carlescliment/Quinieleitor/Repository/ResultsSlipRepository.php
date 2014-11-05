<?php

namespace carlescliment\Quinieleitor\Repository;

use carlescliment\Quinieleitor\ResultsSlip,
    carlescliment\Quinieleitor\Match;

use carlescliment\Components\DataBase\Connection;


class ResultsSlipRepository
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function loadCurrent()
    {
        $this->connection->execute('SELECT * FROM `betting_slips` WHERE date>NOW() AND closed=0 ORDER BY date ASC LIMIT 1');
        if ($slip_data = $this->connection->fetch()) {
            return $this->buildSlipFromData($slip_data);
        }

        return null;
    }

    public function load($slip_id)
    {
        $this->connection->execute('SELECT * FROM `betting_slips` WHERE id=:id', array(
            ':id' => $slip_id));
        $slip_data = $this->connection->fetch();
        if ($slip_data) {
            return $this->buildSlipFromData($slip_data);
        }

        return null;
    }

    public function loadAll()
    {
        $slips = array();
        $this->connection->execute('SELECT * FROM `betting_slips` ORDER BY date ASC');
        while ($slip_data = $this->connection->fetch()) {
            $slips[] = $this->buildSlipFromData($slip_data);
        }

        return $slips;
    }

    public function save(ResultsSlip $slip)
    {
        $this->connection->execute('INSERT INTO `betting_slips` (date, closed) VALUES (:date, :closed)', array(
            ':date' => $slip->getDate()->format('Y-m-d'),
            ':closed' => (int)$slip->isClosed(),
        ));

        return $this->connection->lastInsertId();
    }


    private function buildSlipFromData(array $slip_data)
    {
        $slip = new ResultsSlip($slip_data['id'], new \DateTime($slip_data['date']));
        if ($slip_data['closed']) {
            $slip->close();
        }
        $this->loadSlipMatches($slip);

        return $slip;
    }

    private function loadSlipMatches(ResultsSlip $slip)
    {
        $this->connection->execute('SELECT * FROM `matches` WHERE slip_id=:slip_id ORDER BY id ASC', array(
            ':slip_id' => $slip->getId()));
        while ($match = $this->connection->fetch()) {
            $slip->add(new Match($match['id'], $match['name'], $match['result']));
        }
    }
}