<?php

namespace App\Support;

final class VisitorQuestionnaire
{
    public const VERSION = '1';

    public const QUESTIONS = [
        'q1' => 'Entendi como começar a navegação.',
        'q2' => 'Foi fácil avançar, voltar e retornar ao início.',
        'q3' => 'Os textos e as informações apresentados estavam claros.',
        'q4' => 'A interface respondeu aos meus comandos de forma satisfatória.',
        'q5' => 'Fiquei satisfeito com a experiência de navegação.',
    ];

    public const OPTIONS = [
        '1' => 'Discordo totalmente', '2' => 'Discordo', '3' => 'Nem concordo nem discordo',
        '4' => 'Concordo', '5' => 'Concordo totalmente', 'na' => 'Não consigo avaliar',
    ];
}
