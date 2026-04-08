<?php

namespace Tests\Unit\Models\Compliance;

use App\Models\Compliance\EddQuestionnaireTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EddQuestionnaireTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_template(): void
    {
        $template = EddQuestionnaireTemplate::create([
            'name' => 'Standard EDD',
            'version' => 1,
            'is_active' => true,
            'questions' => [
                ['section' => 'personal', 'question' => 'What is your occupation?', 'required' => true],
                ['section' => 'personal', 'question' => 'What is your employer name?', 'required' => true],
            ],
        ]);

        $this->assertDatabaseHas('edd_questionnaire_templates', [
            'name' => 'Standard EDD',
            'version' => 1,
            'is_active' => true,
        ]);
    }

    public function test_questions_cast_to_array(): void
    {
        $questions = [
            ['section' => 'source_of_funds', 'question' => 'How did you acquire these funds?', 'required' => true],
            ['section' => 'source_of_funds', 'question' => 'Please provide documentation', 'required' => false],
        ];

        $template = EddQuestionnaireTemplate::create([
            'name' => 'High Risk EDD',
            'version' => 1,
            'is_active' => true,
            'questions' => $questions,
        ]);

        $template->refresh();

        $this->assertIsArray($template->questions);
        $this->assertCount(2, $template->questions);
        $this->assertEquals('source_of_funds', $template->questions[0]['section']);
    }

    public function test_get_active_templates_only(): void
    {
        EddQuestionnaireTemplate::create([
            'name' => 'Active Template',
            'version' => 1,
            'is_active' => true,
            'questions' => [],
        ]);

        EddQuestionnaireTemplate::create([
            'name' => 'Inactive Template',
            'version' => 1,
            'is_active' => false,
            'questions' => [],
        ]);

        $activeTemplates = EddQuestionnaireTemplate::getActiveTemplates()->get();

        $this->assertCount(1, $activeTemplates);
        $this->assertEquals('Active Template', $activeTemplates->first()->name);
    }

    public function test_is_complete_returns_true_when_all_required_fields_present(): void
    {
        $template = EddQuestionnaireTemplate::create([
            'name' => 'Test EDD',
            'version' => 1,
            'is_active' => true,
            'questions' => [
                ['id' => 'q1', 'question' => 'What is your occupation?', 'required' => true],
                ['id' => 'q2', 'question' => 'Optional question?', 'required' => false],
            ],
        ]);

        $responses = [
            'q1' => 'Employed as engineer',
            'q2' => 'Optional answer',
        ];

        $this->assertTrue($template->isComplete($responses));
    }

    public function test_is_complete_returns_false_when_required_field_missing(): void
    {
        $template = EddQuestionnaireTemplate::create([
            'name' => 'Test EDD',
            'version' => 1,
            'is_active' => true,
            'questions' => [
                ['id' => 'q1', 'question' => 'What is your occupation?', 'required' => true],
                ['id' => 'q2', 'question' => 'What is your employer?', 'required' => true],
            ],
        ]);

        $responses = [
            'q1' => 'Employed as engineer',
            // q2 is missing
        ];

        $this->assertFalse($template->isComplete($responses));
    }
}
