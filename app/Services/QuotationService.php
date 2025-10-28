<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

use App\Enums\QuotationStatusEnum;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use Auth;

use Carbon\Carbon;

class QuotationService
{
    public function getHeadCount(Lead $lead, ?string $state): string
    {
        return $lead->find($state)->company_size;
    }

    public function getLeadName(Lead $lead, ?string $state): string
    {
        return $lead->find($state)->company_name;
    }

    public function searchLeadByName(Lead $lead, ?string $search): array
    {
        if (auth('web')->user()->role == User::IS_USER) {
            $companies = $lead->where('company_name','like',"%{$search}%")->where('sales_person_id', auth('web')->user()->id)->limit(10)->pluck('name','id');
            return $companies->map(fn($item) => Str::upper($item))->toArray();
        }
        $companies = $lead->where('company_name','like',"%{$search}%")->limit(10)->pluck('name','id');
        return $companies->map(fn($item) => Str::upper($item))->toArray();
    }

    public function getLeadList(Lead $lead): array
    {
        if (auth('web')->user()->role == User::IS_USER) {
            $companies = $lead->where('sales_person_id', auth('web')->user()->id)->pluck('company_name','id');
            return $companies->map(fn($item) => Str::upper($item))->toArray();
        }

        $companies = $lead->pluck('company_name','id');
        return $companies->map(fn($item) => Str::upper($item))->toArray();
    }

    public function filterByLeadName(Builder $query, array $data): ?Builder
    {
        if (!$data) {
            return null;
        }

        // return $query->whereHas('lead', function($q) use($data) {
        //     $q->where('name','like',"%{$data['lead_name']}%");
        // });
        return null;
    }

    public function getQuotationList(Quotation $quotation): array
    {
        return $quotation->pluck('quotation_reference_no','quotation_reference_no')->toArray();
    }

    public function getProformaInvoiceList(Quotation $quotation): array
    {
        return $quotation->pluck('pi_reference_no','pi_reference_no')->toArray();
    }

    public function searchQuotationByReferenceNo(Quotation $quotation, ?string $search): array
    {
        return $quotation->where('quotation_reference_no', 'like', "%{$search}%")->limit(10)->pluck('quotation_reference_no','quotation_reference_no')->toArray();
    }

    public function searchProformaInvoiceByReferenceNo(Quotation $quotation, ?string $search): array
    {
        return $quotation->where('pi_reference_no', 'like', "%{$search}%")->limit(10)->pluck('pi_reference_no','pi_reference_no')->toArray();
    }

    public function searchQuotationByDate(Builder $query, ?array $data): Builder
    {
        if ($data['quotation_date']) {
            $date = Carbon::createFromFormat('j M Y', $data['quotation_date'])->format('Y-m-d');

            return $query->where('quotation_date', $date);
        }

        return $query;
    }

    public function getProductList(Product $product, ?string $choice): array
    {
        //info("Choice: {$choice}");
        if ($choice == 'hrdf') {
            return $product->active()->where('solution',$choice)->pluck('code','id')->toArray();
        }
        if ($choice == 'product') {
            return $product->active()->where('solution','<>','hrdf')->pluck('code','id')->toArray();
        }

        // if ($choice == 'other') {
        //     return $product->active()->where('solution',$choice)->pluck('code','id')->toArray();
        // }

        return [];
    }

    public function getProductByState(Product $product, ?string $state): Collection
    {
        return $product->find($state);
    }

    public function duplicate(Quotation $quotation): void
    {
        $newQuotation = $quotation->replicate();
        /**
         * remove items_sum_total_after_tax field
         * as this field is not an attribute in DB.
         * It is used for displaying quotation total value after tax
         * in the quotation listing only............................................
         */
        $newQuotation->offsetUnset('items_sum_total_before_tax');
        /**
         * set newly cloned quotation status and date
         */
        $newQuotation->status = QuotationStatusEnum::new;
        $newQuotation->quotation_date = now()->format('Y-m-d');
        $newQuotation->mark_as_final = false;
        $newQuotation->sales_person_id = auth()->id();
        $newQuotation->push();

        /**
         * update quotation reference no to ensure that this is unique.
         */
        $newQuotation->quotation_reference_no = $this->update_reference_no($newQuotation);
        $newQuotation->save();

        $items = $quotation->items;
        $items->each(function($item) use($newQuotation) {
            $clonedItem = $item->replicate();
            $newQuotation->items()->save($clonedItem);
        });
    }

    public function update_reference_no(Quotation $quotation): string
    {
        /**
         * change reference code
         */
        $max_num = 9999;
        $starting_number = 0;
        $reference_number = $starting_number + $quotation->id;
        $year = now()->format('y');
        $num = $reference_number%$max_num == 0 ? $max_num : ($reference_number%$max_num);
        return 'TTC/' .  Str::upper($quotation->sales_person->code) . '/' . sprintf('%02d%04d', $year,$num);
        // return  $year . sprintf('%04d',$num) . '/' . Str::upper(auth()->user()->code);
    }

    public function update_pi_reference_no(Quotation $quotation): string
    {
        $max_num = 9999;
        $starting_number = 0;
        $reference_number = $starting_number + $quotation->id;
        $year = now()->format('y');
        $num = $reference_number%$max_num == 0 ? $max_num : ($reference_number%$max_num);
        return 'PI/' .  Str::upper($quotation->sales_person->code) . '/' . sprintf('%02d%04d', $year,$num);
    }

    public function getUserList(): array
    {
        if (auth('web')->user()->role == User::IS_USER) {
            return User::find(auth('web')->user()->id)->pluck('name','id')->toArray();
        }

        return User::query()->pluck('name','id')->toArray();
    }

    public function getSalesPersonName(User $user, ?string $state): string
    {
        return $user->find($state)->name;
    }

    public function searchSalesPersonName(User $user, ?string $search): array
    {
        return $user->where('name','like',"%{$search}%")->limit(10)->pluck('name','id')->toArray();
    }

    public function getDepartmentCode(?string $departmentName): string
    {
        return match($departmentName) {
            'HR Solution' => 'HR',
            'Fingertec' => 'FT',
            'iNeighbour' => 'TC',
        };
    }
}
