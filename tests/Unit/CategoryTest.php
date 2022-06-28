<?php

namespace Tests\Unit;


use App\Http\Controllers\Category\CategoryController;
use Tests\TestCase;

class CategoryTest extends TestCase
{

    public function testBadFileExtension() {
        $resultMessage = CategoryController::parseData('fawfawf.html');
        $this->assertEquals('Неверный формат файла',$resultMessage->getData()->message);
    }

    public function testFileNotExist(){
        $resultMessage = CategoryController::parseData('fawfawf.csv');
        $this->assertEquals('Файл не существует или нет прав доступа',$resultMessage->getData()->message);
    }

    public function testJsonReadFileError(){
        $resultMessage = CategoryController::parseData('fawfawf.json');
        $this->assertEquals('Json файл не найден',$resultMessage->getData()->message);
    }

    public function testJror(){
        $resultMessage = CategoryController::parseData('fawfawf.json');
        $this->assertEquals('Json файл не найден',$resultMessage->getData()->message);
    }
}
