<?php


namespace App\Http\Controllers\Category;


use App\Http\Controllers\Controller;
use App\Models\Category;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function parseData(string $file_path = 'category.csv'):JsonResponse
    {
        //Получаем полный путь к файлу из storage/app/public/ и смотрим расшитрение
        $url = Storage::disk('public')->path($file_path);
        $extension = pathinfo($url, PATHINFO_EXTENSION);

        if($extension === 'csv'){
            //конвертируем csv в массив
            $cvsArray = self::csv_to_array($url,';');

            if($cvsArray){
                if(
                    array_key_exists('category', $cvsArray[0]) &&
                    array_key_exists('parent_id', $cvsArray[0]) &&
                    array_key_exists('id', $cvsArray[0])
                ){
                    //Обновляем данные в базе
                    $errors = self::updateData($cvsArray);
                    if(!empty($errors['update']) || !empty($errors['insert'])){
                        return response()->json(
                            self::errorsMessage(
                                'Не обновилось : ' . implode(",", $errors['update']) .
                                'Не добавилось : ' . implode(",", $errors['insert'])
                            )
                        );
                    }
                }else{
                    return response()->json(self::errorsMessage('Неверный формат csv файла'));
                }
            }else{
                return response()->json(self::errorsMessage('Файл не существует или нет прав доступа'));
            }
        }else if($extension === 'json'){
            //получаем данные из json
            try {
                $contents = Storage::disk('public')->get($file_path);
            } catch ( Exception $e) {
                return response()->json(self::errorsMessage('Json файл не найден'));
            }
            $jsonArray = json_decode($contents);

            if($jsonArray){
                //Обновляем данные в базе
                self::updateDataFromJson($jsonArray);
            }else{
                return response()->json(self::errorsMessage('Ошибка при чтении JSON файла'));
            }
        }else{
            return response()->json(self::errorsMessage('Неверный формат файла'));
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Данные успешно обновлены'
        ]);
    }

    private function updateData(array $fileContent): array
    {
        $category = Category::query()->pluck("id")->toArray();
        $insertCategory = [];
        $updateCategoryId = [];
        $updateCategoryFields = [];
        $errors = ['update' => [], 'insert' => []];

        foreach ($fileContent as $catKey){
            //Если id из файла есть в базе, то добавляем в массив на обновление, иначе на добавление
            if(in_array((int)$catKey["id"], $category, true)){
                $updateCategoryId[] = (int)$catKey["id"];
                $parent_id = $catKey["parent_id"] === "" ? null : (int)$catKey["parent_id"];
                $updateCategoryFields[(int)$catKey["id"]] = ["category" => $catKey["category"], "parent_id" => $parent_id];
            }else{
                $parent_id = $catKey["parent_id"] === "" ? null : (int)$catKey["parent_id"];
                $insertCategory[] = [
                    "id" => (int)$catKey["id"],
                    "category" => $catKey["category"],
                    "parent_id" => $parent_id
                ];
            }
        }

        if(!empty($updateCategoryId)){
            foreach ($updateCategoryId as $id){
                //Обновляем данные в базе по id и собираем те, что не обновились
                if(!Category::where("id", '=', $id)->update($updateCategoryFields[$id])){
                    $errors['update'][] = $id;
                }

            }
        }

        if(!empty($insertCategory)){
            //Добавляем данные в базу и собираем то, что не добавилось
            if(!Category::insert($insertCategory)){
                $errors['insert'][] = $insertCategory;
            }
        }

        return $errors;
    }

    private function updateDataFromJson(array $jsonFileContent){
        $result = self::recursiveParseCategory($jsonFileContent);
        self::updateData($result);
    }

    public function csv_to_array($filename = '', $delimiter = ',') {
        if (!file_exists($filename) || !is_readable($filename))
            return FALSE;

        $header = NULL;
        $data = array();
        if (($handle = fopen($filename, 'rb')) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                if (!$header)
                    $header = $row;
                else
                    $data[] = array_combine($header, $row);
            }
            fclose($handle);
        }
        return $data;
    }

    public function recursiveParseCategory($json_array, $parent_id = null) {
       $result = [];
       foreach ($json_array as $category){
           if(isset($category->subcategories)){
               $result = array_merge( $result, self::recursiveParseCategory($category->subcategories, $category->id));
           }
           $result[] = ['id' => $category->id, 'category' => $category->category, 'parent_id' => $parent_id];
       }
       return $result;
    }

    private function errorsMessage($message){
        return [
                'status' => 'error',
                'message' => $message,
            ];
    }
}
