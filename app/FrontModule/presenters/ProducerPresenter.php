<?php


namespace App\FrontModule\Presenters;


use App\Model\Producer\ProducerRepository;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;

class ProducerPresenter extends BasePresenter
{

    /**
     * @var ProducerRepository
     * @inject
     */
    public $producerRepository;

    public function renderDefault()
    {
        $producers = $this->producerRepository->getAll()->order('name');
        $letters = [];
        foreach ($producers as $producer) {
            if (isset($letters[$producer->name[0]])) {
                continue;
            }
            $letters[$producer->name[0]] = strtoupper($producer->name[0]);
        }
        $out = [];
        foreach ($letters as $letter) {
            foreach ($producers as $producer) {
                if (Strings::startsWith($producer->name, $letter)) {
                    $out[$letter][] = [
                        'name' => $producer->name,
                        'slug' => $producer->slug
                    ];
                }
            }
        }
        $this->template->out = $out;
    }
}