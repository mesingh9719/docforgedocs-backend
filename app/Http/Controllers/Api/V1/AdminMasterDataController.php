<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Currency;
use App\Models\Industry;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Tax;
use Illuminate\Support\Facades\Validator;

class AdminMasterDataController extends Controller
{
    protected $models = [
        'currencies' => Currency::class,
        'industries' => Industry::class,
        'countries' => Country::class,
        'states' => State::class,
        'cities' => City::class,
        'taxes' => Tax::class,
    ];

    public function index(Request $request, $type)
    {
        if (!array_key_exists($type, $this->models)) {
            return response()->json(['message' => 'Invalid master data type'], 404);
        }

        $modelClass = $this->models[$type];
        $query = $modelClass::query();

        // Specific filters
        if ($type === 'states' && $request->country_id) {
            $query->where('country_id', $request->country_id);
        }
        if ($type === 'cities' && $request->state_id) {
            $query->where('state_id', $request->state_id);
        }

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->has('active_only')) {
            $query->where('is_active', true);
        }

        // Pagination or List
        if ($request->has('all')) {
            return response()->json($query->get());
        }

        return response()->json($query->paginate(10));
    }

    public function store(Request $request, $type)
    {
        if (!array_key_exists($type, $this->models)) {
            return response()->json(['message' => 'Invalid master data type'], 404);
        }

        $validator = $this->getValidator($request, $type);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $modelClass = $this->models[$type];
        $item = $modelClass::create($request->all());

        return response()->json(['message' => 'Created successfully', 'data' => $item], 201);
    }

    public function update(Request $request, $type, $id)
    {
        if (!array_key_exists($type, $this->models)) {
            return response()->json(['message' => 'Invalid master data type'], 404);
        }

        $modelClass = $this->models[$type];
        $item = $modelClass::find($id);

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $validator = $this->getValidator($request, $type, $id);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $item->update($request->all());

        return response()->json(['message' => 'Updated successfully', 'data' => $item]);
    }

    public function destroy($type, $id)
    {
        if (!array_key_exists($type, $this->models)) {
            return response()->json(['message' => 'Invalid master data type'], 404);
        }

        $modelClass = $this->models[$type];
        $item = $modelClass::find($id);

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $item->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    protected function getValidator(Request $request, $type, $id = null)
    {
        $rules = [
            'currencies' => [
                'code' => 'required|string|max:3|unique:currencies,code,' . $id,
                'name' => 'required|string',
                'symbol' => 'nullable|string',
            ],
            'industries' => [
                'name' => 'required|string|unique:industries,name,' . $id,
                'slug' => 'required|string|unique:industries,slug,' . $id,
            ],
            'countries' => [
                'name' => 'required|string',
                'iso_code' => 'required|string|max:2|unique:countries,iso_code,' . $id,
                'currency_code' => 'nullable|string|max:3',
            ],
            'states' => [
                'country_id' => 'required|exists:countries,id',
                'name' => 'required|string',
            ],
            'cities' => [
                'state_id' => 'required|exists:states,id',
                'name' => 'required|string',
            ],
            'taxes' => [
                'name' => 'required|string',
                'rate' => 'required|numeric|min:0',
                'type' => 'required|in:percentage,fixed',
            ],
        ];

        return Validator::make($request->all(), $rules[$type] ?? []);
    }
}
