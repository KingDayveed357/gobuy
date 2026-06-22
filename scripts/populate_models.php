<?php

$files = [
    'app/Modules/Catalog/Models/ProductAttribute.php' => "<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAttribute extends Model
{
    protected \$guarded = [];

    public function values(): HasMany
    {
        return \$this->hasMany(ProductAttributeValue::class);
    }
}
",
    'app/Modules/Catalog/Models/ProductAttributeValue.php' => "<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttributeValue extends Model
{
    protected \$guarded = [];

    public function attribute(): BelongsTo
    {
        return \$this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }
}
",
    'app/Modules/Catalog/Models/ProductVariant.php' => "<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Inventory\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariant extends Model
{
    protected \$guarded = [];

    public function product(): BelongsTo
    {
        return \$this->belongsTo(Product::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return \$this->belongsToMany(ProductAttributeValue::class, 'variant_attribute_values');
    }

    public function inventory(): HasOne
    {
        return \$this->hasOne(Inventory::class);
    }
}
",
    'app/Modules/Inventory/Models/Inventory.php' => "<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    protected \$guarded = [];

    public function variant(): BelongsTo
    {
        return \$this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function movements(): HasMany
    {
        return \$this->hasMany(StockMovement::class);
    }
}
",
    'app/Modules/Inventory/Models/StockMovement.php' => "<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected \$guarded = [];

    public function inventory(): BelongsTo
    {
        return \$this->belongsTo(Inventory::class);
    }
}
",
    'app/Modules/Tags/Models/Tag.php' => "<?php

namespace App\Modules\Tags\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    protected \$guarded = [];
}
",
    'app/Modules/Logistics/Models/ShippingZone.php' => "<?php

namespace App\Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    protected \$guarded = [];

    protected \$casts = [
        'regions' => 'array',
        'is_active' => 'boolean',
    ];

    public function rates(): HasMany
    {
        return \$this->hasMany(ShippingRate::class);
    }
}
",
    'app/Modules/Logistics/Models/ShippingMethod.php' => "<?php

namespace App\Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    protected \$guarded = [];

    protected \$casts = [
        'is_active' => 'boolean',
    ];
}
",
    'app/Modules/Logistics/Models/ShippingRate.php' => "<?php

namespace App\Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    protected \$guarded = [];

    public function zone(): BelongsTo
    {
        return \$this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function method(): BelongsTo
    {
        return \$this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }
}
",
];

foreach ($files as $path => $content) {
    file_put_contents(__DIR__.'/../'.$path, $content);
}

echo "Models updated successfully.\n";
