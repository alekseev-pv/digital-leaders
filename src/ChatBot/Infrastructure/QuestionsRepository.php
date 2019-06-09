<?php


namespace App\ChatBot\Infrastructure;


class QuestionsRepository
{

    public function all(): array
    {
        // todo add remote request of questions. MVP feature only.
        return [
            'Когда планируют отключить горячую воду?' => 'В понедельник 12.09.2019',
            'Когда будет кап ремонт?' => 'Планируемая дата капитального ремонта: март 2021 года.',
        ];

    }

}