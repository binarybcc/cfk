<?php

declare(strict_types=1);

namespace Tests\Unit;

use CFK\Validators\ChildValidator;
use PHPUnit\Framework\TestCase;

class ChildValidatorTest extends TestCase
{
    private ChildValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ChildValidator();
    }

    /** @test */
    public function it_validates_correct_child_data(): void
    {
        $data = [
            'family_id' => '175A',
            'name' => 'John Doe',
            'age' => 10,
            'gender' => 'M',
            'grade' => '5th',
            'interests' => 'Soccer, reading'
        ];

        $isValid = $this->validator->validate($data);

        $this->assertTrue($isValid);
        $this->assertEmpty($this->validator->getErrors());
    }

    /** @test */
    public function it_rejects_missing_required_fields(): void
    {
        $data = [];

        $isValid = $this->validator->validate($data);

        $this->assertFalse($isValid);
        $errors = $this->validator->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Family ID is required', implode(', ', $errors));
    }

    /** @test */
    public function it_validates_family_id_format(): void
    {
        // Invalid format - no letter
        $data1 = [
            'family_id' => '175',
            'name' => 'Test',
            'age' => 10,
            'gender' => 'M'
        ];
        $this->assertFalse($this->validator->validate($data1));
        $this->assertStringContainsString('Family ID must be in format', $this->validator->getFirstError());

        // Valid format
        $data2 = [
            'family_id' => '175A',
            'name' => 'Test',
            'age' => 10,
            'gender' => 'M'
        ];
        $this->assertTrue($this->validator->validate($data2));
    }

    /** @test */
    public function it_validates_name_length(): void
    {
        // Too short
        $data1 = [
            'family_id' => '175A',
            'name' => 'A',
            'age' => 10,
            'gender' => 'M'
        ];
        $this->assertFalse($this->validator->validate($data1));
        $this->assertStringContainsString('at least 2 characters', $this->validator->getFirstError());

        // Too long
        $data2 = [
            'family_id' => '175A',
            'name' => str_repeat('A', 101),
            'age' => 10,
            'gender' => 'M'
        ];
        $this->assertFalse($this->validator->validate($data2));
        $this->assertStringContainsString('too long', $this->validator->getFirstError());
    }

    /** @test */
    public function it_validates_name_characters(): void
    {
        $data = [
            'family_id' => '175A',
            'name' => 'John123',  // Contains numbers
            'age' => 10,
            'gender' => 'M'
        ];

        $isValid = $this->validator->validate($data);

        $this->assertFalse($isValid);
        $this->assertStringContainsString('invalid characters', $this->validator->getFirstError());
    }

    /** @test */
    public function it_accepts_valid_name_with_special_characters(): void
    {
        $names = [
            "Mary-Jane O'Connor",
            "José García",
            "Li Wei-Chen"
        ];

        foreach ($names as $name) {
            $data = [
                'family_id' => '175A',
                'name' => $name,
                'age' => 10,
                'gender' => 'M'
            ];
            $this->assertTrue($this->validator->validate($data), "Failed for name: {$name}");
        }
    }

    /** @test */
    public function it_validates_age_range(): void
    {
        // Too young
        $data1 = [
            'family_id' => '175A',
            'name' => 'Test',
            'age' => 0,
            'gender' => 'M'
        ];
        $this->assertFalse($this->validator->validate($data1));
        $this->assertStringContainsString('positive number', $this->validator->getFirstError());

        // Too old
        $data2 = [
            'family_id' => '175A',
            'name' => 'Test',
            'age' => 26,
            'gender' => 'M'
        ];
        $this->assertFalse($this->validator->validate($data2));
        $this->assertStringContainsString('cannot be greater than 25', $this->validator->getFirstError());

        // Valid age
        $data3 = [
            'family_id' => '175A',
            'name' => 'Test',
            'age' => 10,
            'gender' => 'M'
        ];
        $this->assertTrue($this->validator->validate($data3));
    }

    /** @test */
    public function it_validates_gender_values(): void
    {
        $validGenders = ['M', 'F', 'male', 'female', 'boy', 'girl'];

        foreach ($validGenders as $gender) {
            $data = [
                'family_id' => '175A',
                'name' => 'Test',
                'age' => 10,
                'gender' => $gender
            ];
            $this->assertTrue($this->validator->validate($data), "Failed for gender: {$gender}");
        }

        // Invalid gender
        $data = [
            'family_id' => '175A',
            'name' => 'Test',
            'age' => 10,
            'gender' => 'other'
        ];
        $this->assertFalse($this->validator->validate($data));
    }

    /** @test */
    public function it_validates_interests_length(): void
    {
        $data = [
            'family_id' => '175A',
            'name' => 'Test',
            'age' => 10,
            'gender' => 'M',
            'interests' => str_repeat('A', 501)
        ];

        $isValid = $this->validator->validate($data);

        $this->assertFalse($isValid);
        $this->assertStringContainsString('Interests description is too long', $this->validator->getFirstError());
    }

    /** @test */
    public function it_validates_csv_row_with_row_number(): void
    {
        $row = [
            'family_id' => 'invalid',
            'name' => 'Test',
            'age' => 10,
            'gender' => 'M'
        ];

        $isValid = $this->validator->validateCsvRow($row, 5);

        $this->assertFalse($isValid);
        $this->assertStringContainsString('Row 5:', $this->validator->getFirstError());
    }

    /** @test */
    public function it_sanitizes_child_data(): void
    {
        $data = [
            'family_id' => '  175a  ',
            'name' => '  John Doe  ',
            'age' => '10',
            'gender' => ' M ',
            'grade' => '  5th  ',
            'interests' => '  Soccer  '
        ];

        $sanitized = ChildValidator::sanitize($data);

        $this->assertEquals('175A', $sanitized['family_id']);
        $this->assertEquals('John Doe', $sanitized['name']);
        $this->assertEquals(10, $sanitized['age']);
        $this->assertEquals('male', $sanitized['gender']);
        $this->assertEquals('5th', $sanitized['grade']);
        $this->assertEquals('Soccer', $sanitized['interests']);
    }

    /** @test */
    public function it_normalizes_gender_values(): void
    {
        $testCases = [
            ['input' => 'm', 'expected' => 'male'],
            ['input' => 'M', 'expected' => 'male'],
            ['input' => 'male', 'expected' => 'male'],
            ['input' => 'boy', 'expected' => 'male'],
            ['input' => 'f', 'expected' => 'female'],
            ['input' => 'F', 'expected' => 'female'],
            ['input' => 'female', 'expected' => 'female'],
            ['input' => 'girl', 'expected' => 'female'],
        ];

        foreach ($testCases as $case) {
            $data = ['gender' => $case['input']];
            $sanitized = ChildValidator::sanitize($data);
            $this->assertEquals($case['expected'], $sanitized['gender'],
                "Failed to normalize gender: {$case['input']}");
        }
    }

    /** @test */
    public function it_returns_all_errors(): void
    {
        $data = [
            'family_id' => '',  // Missing
            'name' => 'A',      // Too short
            'age' => 0,         // Invalid
            'gender' => ''      // Missing
        ];

        $this->validator->validate($data);
        $errors = $this->validator->getErrors();

        $this->assertGreaterThan(1, count($errors));
        $this->assertStringContainsString('Family ID', implode(', ', $errors));
        $this->assertStringContainsString('Name', implode(', ', $errors));
        $this->assertStringContainsString('Age', implode(', ', $errors));
        $this->assertStringContainsString('Gender', implode(', ', $errors));
    }

    /** @test */
    public function it_returns_first_error(): void
    {
        $data = [
            'family_id' => '',
            'name' => 'A',
            'age' => 0,
            'gender' => ''
        ];

        $this->validator->validate($data);
        $firstError = $this->validator->getFirstError();

        $this->assertNotNull($firstError);
        $this->assertIsString($firstError);
        $this->assertStringContainsString('Family ID', $firstError);
    }

    /** @test */
    public function it_handles_optional_fields(): void
    {
        $data = [
            'family_id' => '175A',
            'name' => 'Test',
            'age' => 10,
            'gender' => 'M'
            // grade and interests are optional
        ];

        $isValid = $this->validator->validate($data);

        $this->assertTrue($isValid);
        $this->assertEmpty($this->validator->getErrors());
    }
}
