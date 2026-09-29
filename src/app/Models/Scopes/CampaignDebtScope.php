<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limit `debts` queries to real campaign debts.
 *
 * A consolidated payment request is stored as a parent `debts` row without a campaign. It is a
 * request, not an extra amount owed, so every ledger total, credit check and report must skip it.
 * Applying the rule as a global scope keeps all existing Debt queries from counting it twice;
 * payment-request code opts out explicitly through Debt::paymentRequests().
 *
 * The rule tests `campaign_id` instead of an EXISTS on `parent_id` because MySQL rejects a
 * self-referencing subquery inside UPDATE/DELETE statements on the same table.
 */
class CampaignDebtScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder<Model> $builder Query being built.
     * @param Model $model Debt model instance.
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNotNull($model->qualifyColumn('campaign_id'));
    }
}
