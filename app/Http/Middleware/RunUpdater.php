<?php

    namespace App\Http\Middleware;

    use App\User;
    use Closure;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Artisan;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;
    use Modules\Booking\Models\Booking;
    use Modules\Booking\Models\Service;
    use Modules\Booking\Models\ServiceTranslation;
    use Modules\Car\Models\Car;
    use Modules\Car\Models\CarTranslation;
    use Modules\Core\Models\Settings;
    use Modules\Event\Models\EventTranslation;
    use Modules\Flight\Models\Airline;
    use Modules\Flight\Models\Airport;
    use Modules\Flight\Models\BookingPassengers;
    use Modules\Flight\Models\Flight;
    use Modules\Flight\Models\FlightSeat;
    use Modules\Hotel\Models\Hotel;
    use Modules\Hotel\Models\HotelRoom;
    use Modules\Hotel\Models\HotelTranslation;
    use Modules\Location\Models\LocationCategory;
    use Modules\Location\Models\LocationCategoryTranslation;
    use Modules\Review\Models\Review;
    use Modules\Space\Models\Space;
    use Modules\Space\Models\SpaceTranslation;
    use Modules\Tour\Models\Tour;
    use Modules\Event\Models\Event;
    use Modules\Tour\Models\TourTranslation;
    use Modules\User\Emails\CreditPaymentEmail;
    use Spatie\Permission\Models\Permission;
    use Spatie\Permission\Models\Role;
    use Bavix\Wallet\Models\Transaction;
    use Bavix\Wallet\Models\Transfer;
    use Bavix\Wallet\Models\Wallet;

    class RunUpdater
    {
        /**
         * Handle an incoming request.
         *
         * @param  \Illuminate\Http\Request  $request
         * @param  \Closure  $next
         * @return mixed
         */
        public function handle($request, Closure $next)
        {
            if (strpos($request->path(), 'install') === false && file_exists(storage_path().'/installed') and !app()->runningInConsole()) {

                $this->updateTo110();
                $this->updateTo120();
                $this->updateTo130();
                $this->updateTo140();
                $this->updateTo150();
                $this->updateTo151();
                $this->updateTo160();
                $this->updateTo170();
                $this->updateTo180();
                $this->updateTo190();
                $this->updateTo200();
                $this->updateTo210();
                $this->updateTo220();
                $this->updateTo230();
                $this->updateTo240();
            }
            return $next($request);
        }

        public function updateTo192()
        {
            if (setting_item('update_to_192')) {
                return false;
            }

            Artisan::call('migrate', [
                '--force' => true,
            ]);



            Artisan::call('cache:clear');
        }

        public function updateTo110()
        {
            if (setting_item('update_to_110')) {
                return false;
            }
            Artisan::call('migrate', [
                '--force' => true,
            ]);
            Permission::findOrCreate('dashboard_vendor_access');
            $vendor = Role::findOrCreate('vendor');
            $vendor->givePermissionTo('media_upload');
            $vendor->givePermissionTo('tour_view');
            $vendor->givePermissionTo('tour_create');
            $vendor->givePermissionTo('tour_update');
            $vendor->givePermissionTo('tour_delete');
            $vendor->givePermissionTo('dashboard_vendor_access');
            $role = Role::findOrCreate('administrator');
            $role->givePermissionTo('dashboard_vendor_access');
            Settings::store('update_to_110', true);
            Artisan::call('cache:clear');
        }

        public function updateTo120()
        {
            if (setting_item('update_to_120')) {
                return false;
            }
            Artisan::call('migrate', [
                '--force' => true,
            ]);
            Permission::findOrCreate('space_view');
            Permission::findOrCreate('space_create');
            Permission::findOrCreate('space_update');
            Permission::findOrCreate('space_delete');
            Permission::findOrCreate('space_manage_others');
            Permission::findOrCreate('space_manage_attributes');
            // Vendor
            $vendor = Role::findOrCreate('vendor');
            $vendor->givePermissionTo('space_create');
            $vendor->givePermissionTo('space_view');
            $vendor->givePermissionTo('space_update');
            $vendor->givePermissionTo('space_delete');
            // Admin
            $role = Role::findOrCreate('administrator');
            $role->givePermissionTo('space_view');
            $role->givePermissionTo('space_create');
            $role->givePermissionTo('space_update');
            $role->givePermissionTo('space_delete');
            $role->givePermissionTo('space_manage_others');
            $role->givePermissionTo('space_manage_attributes');

            if (empty(setting_item('topbar_left_text'))) {
                DB::table('core_settings')->insert(
                    [
                        'name'  => 'topbar_left_text',
                        'val'   => '<div class="socials">
    <a href="#"><i class="fa fa-facebook"></i></a>
    <a href="#"><i class="fa fa-linkedin"></i></a>
    <a href="#"><i class="fa fa-google-plus"></i></a>
</div>
<span class="line"></span>
<a href="mailto:contact@myTravel.com">contact@myTravel.com</a>',
                        'group' => "general",
                    ]
                );
            }
            Settings::store('update_to_120', true);
            Artisan::call('cache:clear');
        }

        public function updateTo130()
        {
            if (setting_item('update_to_130')) {
                return false;
            }
            Artisan::call('migrate', [
                '--force' => true,
            ]);
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'vendor_commission_amount')) {
                    $table->integer('vendor_commission_amount')->nullable();
                    $table->decimal('total_before_fees', 10, 2)->nullable();
                }
                if (!Schema::hasColumn('users', 'vendor_commission_type')) {
                    $table->string('vendor_commission_type', 30)->nullable();
                }
            });
            $this->__updateReviewVendorId();
            // Fix null status user
            User::query()->whereRaw('status is NULL')->update([
                'status' => 'publish'
            ]);
            Settings::store('update_to_130', true);
            Artisan::call('cache:clear');
        }

        public function updateTo140()
        {

            if (setting_item('update_to_140')) {
                return false;
            }
            Artisan::call('migrate', [
                '--force' => true,
            ]);
            Permission::findOrCreate('vendor_payout_view');
            Permission::findOrCreate('vendor_payout_manage');

            Permission::findOrCreate('hotel_view');
            Permission::findOrCreate('hotel_create');
            Permission::findOrCreate('hotel_update');
            Permission::findOrCreate('hotel_delete');
            Permission::findOrCreate('hotel_manage_others');
            Permission::findOrCreate('hotel_manage_attributes');

            // Admin
            $role = Role::findOrCreate('administrator');
            $role->givePermissionTo('vendor_payout_view');
            $role->givePermissionTo('vendor_payout_manage');
            $role->givePermissionTo('hotel_view');
            $role->givePermissionTo('hotel_create');
            $role->givePermissionTo('hotel_update');
            $role->givePermissionTo('hotel_delete');
            $role->givePermissionTo('hotel_manage_others');
            $role->givePermissionTo('hotel_manage_attributes');

            $vendor = Role::findOrCreate('vendor');
            $vendor->givePermissionTo('hotel_view');
            $vendor->givePermissionTo('hotel_create');
            $vendor->givePermissionTo('hotel_update');
            $vendor->givePermissionTo('hotel_delete');

            Settings::store('update_to_140', true);
            Artisan::call('cache:clear');
        }

        public function updateTo150()
        {
            if (setting_item('update_to_150')) {
                return false;
            }
            Artisan::call('migrate', [
                '--force' => true,
            ]);
            Permission::findOrCreate('plugin_manage');
            $role = Role::findOrCreate('administrator');
            $role->givePermissionTo('plugin_manage');

            // Car
            Permission::findOrCreate('car_view');
            Permission::findOrCreate('car_create');
            Permission::findOrCreate('car_update');
            Permission::findOrCreate('car_delete');
            Permission::findOrCreate('car_manage_others');
            Permission::findOrCreate('car_manage_attributes');
            // Vendor
            $vendor = Role::findOrCreate('vendor');
            $vendor->givePermissionTo('car_create');
            $vendor->givePermissionTo('car_view');
            $vendor->givePermissionTo('car_update');
            $vendor->givePermissionTo('car_delete');
            // Admin
            $role = Role::findOrCreate('administrator');
            $role->givePermissionTo('car_view');
            $role->givePermissionTo('car_create');
            $role->givePermissionTo('car_update');
            $role->givePermissionTo('car_delete');
            $role->givePermissionTo('car_manage_others');
            $role->givePermissionTo('car_manage_attributes');

            Settings::store('update_to_150', true);
            Artisan::call('cache:clear');
        }

        public function updateTo151()
        {
            if (setting_item('update_to_151')) {
                return false;
            }
            Artisan::call('migrate', [
                '--force' => true,
            ]);
            $allServices = get_bookable_services();
            foreach ($allServices as $service) {
                $alls = $service::query()->whereNull('review_score')->get();
                if (!empty($alls)) {
                    foreach ($alls as $item) {
                        $item->update_service_rate();
                    }
                }
            }

            Schema::table(Tour::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(Tour::getTableName(), 'ical_import_url')) {
                    $table->string('ical_import_url')->nullable();
                }
            });
            Schema::table(Space::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(Space::getTableName(), 'ical_import_url')) {
                    $table->string('ical_import_url')->nullable();
                }
            });
            Schema::table(Hotel::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(Hotel::getTableName(), 'ical_import_url')) {
                    $table->string('ical_import_url')->nullable();
                }
            });
            Schema::table(Car::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(Car::getTableName(), 'ical_import_url')) {
                    $table->string('ical_import_url')->nullable();
                }
            });

            Schema::table(CarTranslation::getTableName(), function (Blueprint $table) {
                if (Schema::hasColumn(CarTranslation::getTableName(), 'extra_price')) {
                    $table->dropColumn('extra_price');
                }
            });
            Schema::table(SpaceTranslation::getTableName(), function (Blueprint $table) {
                if (Schema::hasColumn(SpaceTranslation::getTableName(), 'extra_price')) {
                    $table->dropColumn('extra_price');
                }
            });


            DB::statement('ALTER TABLE bc_spaces MODIFY bed integer');
            DB::statement('ALTER TABLE bc_spaces MODIFY bathroom integer');
            DB::statement('ALTER TABLE bc_spaces MODIFY square integer');
            DB::statement('ALTER TABLE bc_hotel_rooms MODIFY size integer');

            Settings::store('update_to_151', true);
            Artisan::call('cache:clear');
        }

        public function updateTo160()
        {
            if (setting_item('update_to_160')) {
                return false;
            }
            Artisan::call('migrate', [
                '--force' => true,
            ]);
            $bookings = Booking::query()->whereIn('status', [
                'paid',
                'completed',
                'completed',
            ])->whereRaw('IFNULL(deposit,0) <= 0 ')->get();
            foreach ($bookings as $booking) {
                if (!$booking->deposit) {
                    $booking->paid = $booking->total;
                    $booking->save();
                }
            }
            Schema::table(HotelRoom::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(HotelRoom::getTableName(), 'ical_import_url')) {
                    $table->string('ical_import_url')->nullable();
                }
            });

            Settings::store('update_to_160', true);
            Artisan::call('cache:clear');
        }

        public function updateTo170()
        {
            if (setting_item('update_to_170')) {
                return false;
            }
            Artisan::call('migrate', [
                '--force' => true,
            ]);
            if (empty(setting_item('tour_map_search_fields'))) {
                DB::table('core_settings')->insert(
                    [
                        'name'  => 'tour_map_search_fields',
                        'val'   => '[{"field":"location","attr":null,"position":"1"},{"field":"category","attr":null,"position":"2"},{"field":"date","attr":null,"position":"3"},{"field":"price","attr":null,"position":"4"},{"field":"advance","attr":null,"position":"5"}]',
                        'group' => 'tour'
                    ]
                );
            }
            if (empty(setting_item('tour_search_fields'))) {
                DB::table('core_settings')->insert(
                    [
                        'name'  => 'tour_search_fields',
                        'val'   => '[{"title":"Location","field":"location","size":"6","position":"1"},{"title":"From - To","field":"date","size":"6","position":"2"}]',
                        'group' => 'tour'
                    ]
                );
            }
            if (empty(setting_item('space_search_fields'))) {
                DB::table('core_settings')->insert(
                    [
                        'name'  => 'space_search_fields',
                        'val'   => '[{"title":"Location","field":"location","size":"4","position":"1"},{"title":"From - To","field":"date","size":"4","position":"2"},{"title":"Guests","field":"guests","size":"4","position":"3"}]',
                        'group' => 'tour'
                    ]
                );
            }
            if (empty(setting_item('hotel_search_fields'))) {
                DB::table('core_settings')->insert(
                    [
                        'name'  => 'hotel_search_fields',
                        'val'   => '[{"title":"Location","field":"location","size":"4","position":"1"},{"title":"Check In - Out","field":"date","size":"5","position":"2"},{"title":"Guests","field":"guests","size":"3","position":"3"}]',
                        'group' => 'hotel'
                    ]
                );
            }
            if (empty(setting_item('car_search_fields'))) {
                DB::table('core_settings')->insert(
                    [
                        'name'  => 'car_search_fields',
                        'val'   => '[{"title":"Location","field":"location","size":"6","position":"1"},{"title":"From - To","field":"date","size":"6","position":"2"}]',
                        'group' => 'car'
                    ]
                );
            }

            if (empty(setting_item('enable_mail_vendor_registered'))) {
                DB::table('core_settings')->insert(
                    [
                        'name'  => 'enable_mail_vendor_registered',
                        'val'   => '1',
                        'group' => 'vendor'
                    ]
                );
                DB::table('core_settings')->insert(
                    [
                        'name'  => 'vendor_content_email_registered',
                        'val'   => '<h1 style="text-align: center;">Welcome!</h1>
                            <h3>Hello [first_name] [last_name]</h3>
                            <p>Thank you for signing up with My Travel! We hope you enjoy your time with us.</p>
                            <p>Regards,</p>
                            <p>My Travel</p>',
                        'group' => 'vendor'
                    ]
                );
            }
            if (empty(setting_item('admin_enable_mail_vendor_registered'))) {
                DB::table('core_settings')->insert(
                    [
                        'name'  => 'admin_enable_mail_vendor_registered',
                        'val'   => '1',
                        'group' => 'vendor'
                    ]
                );
                DB::table('core_settings')->insert(
                    [
                        'name'  => 'admin_content_email_vendor_registered',
                        'val'   => '<h3>Hello Administrator</h3>
                            <p>An user has been registered as Vendor. Please check the information bellow:</p>
                            <p>Full name: [first_name] [last_name]</p>
                            <p>Email: [email]</p>
                            <p>Registration date: [created_at]</p>
                            <p>You can approved the request here: [link_approved]</p>
                            <p>Regards,</p>
                            <p>My Travel</p>',
                        'group' => 'vendor'
                    ]
                );
            }
            if (empty(setting_item('booking_enquiry_enable_mail_to_vendor_content'))) {
                DB::table('core_settings')->insert([
                    [
                        'name'  => "booking_enquiry_enable_mail_to_vendor_content",
                        'val'   => "<h3>Hello [vendor_name]</h3>
                            <p>You get new inquiry request from [email]</p>
                            <p>Name :[name]</p>
                            <p>Emai:[email]</p>
                            <p>Phone:[phone]</p>
                            <p>Content:[note]</p>
                            <p>Service:[service_link]</p>
                            <p>Regards,</p>
                            <p>My Travel</p>
                            </div>",
                        'group' => "enquiry",
                    ]
                ]);
            }
            if (empty(setting_item('booking_enquiry_enable_mail_to_admin_content'))) {
                DB::table('core_settings')->insert([
                    [
                        'name'  => "booking_enquiry_enable_mail_to_admin_content",
                        'val'   => "<h3>Hello Administrator</h3>
                            <p>You get new inquiry request from [email]</p>
                            <p>Name :[name]</p>
                            <p>Emai:[email]</p>
                            <p>Phone:[phone]</p>
                            <p>Content:[note]</p>
                            <p>Service:[service_link]</p>
                            <p>Vendor:[vendor_link]</p>
                            <p>Regards,</p>
                            <p>My Travel</p>",
                        'group' => "enquiry",
                    ],
                ]);
            }

            Schema::table('bc_spaces', function (Blueprint $table) {
                if (Schema::hasColumn('bc_spaces', 'square')) {
                    DB::statement('ALTER TABLE bc_spaces MODIFY square integer');
                }
                if (Schema::hasColumn('bc_spaces', 'max_guests')) {
                    DB::statement('ALTER TABLE bc_spaces MODIFY max_guests integer');
                }
            });

            Permission::findOrCreate('event_view');
            Permission::findOrCreate('event_create');
            Permission::findOrCreate('event_update');
            Permission::findOrCreate('event_delete');
            Permission::findOrCreate('event_manage_others');
            Permission::findOrCreate('event_manage_attributes');
            Permission::findOrCreate('enquiry_view');
            Permission::findOrCreate('enquiry_update');
            Permission::findOrCreate('enquiry_manage_others');

            // Admin
            $role = Role::findOrCreate('administrator');
            $role->givePermissionTo('enquiry_view');
            $role->givePermissionTo('enquiry_update');
            $role->givePermissionTo('enquiry_manage_others');
            $role->givePermissionTo('event_view');
            $role->givePermissionTo('event_create');
            $role->givePermissionTo('event_update');
            $role->givePermissionTo('event_delete');
            $role->givePermissionTo('event_manage_others');
            $role->givePermissionTo('event_manage_attributes');

            // Vendor
            $role = Role::findOrCreate('vendor');
            $role->givePermissionTo('enquiry_view');
            $role->givePermissionTo('enquiry_update');
            $role->givePermissionTo('event_view');
            $role->givePermissionTo('event_create');
            $role->givePermissionTo('event_update');
            $role->givePermissionTo('event_delete');

            Settings::store('update_to_170', true);
            Artisan::call('cache:clear');
        }

        public function updateTo180()
        {
            $this->checkDbEngine();
            if (setting_item('update_to_182')) {
                return "Updated Up 1.8.2";
            }

            Artisan::call('migrate', [
                '--force' => true,
            ]);
            Schema::table('social_posts', function (Blueprint $table) {
                if (!Schema::hasColumn('social_posts', 'privacy')) {
                    $table->string('privacy', 30)->nullable();
                }
            });
            setting_update_item('wallet_credit_exchange_rate', 1);
            setting_update_item('wallet_deposit_rate', 1);
            setting_update_item('wallet_deposit_type', 'list');
            setting_update_item('wallet_deposit_lists', [
                ['name' => __("100$"), 'amount' => 100, 'credit' => 100],
                ['name' => __("Bonus 10%"), 'amount' => 500, 'credit' => 550],
                ['name' => __("Bonus 15%"), 'amount' => 1000, 'credit' => 1150],
            ]);

            setting_update_item('wallet_new_deposit_admin_subject', 'New credit purchase');
            setting_update_item('wallet_new_deposit_admin_content', CreditPaymentEmail::defaultNewBody());
            setting_update_item('wallet_new_deposit_customer_subject', 'Thank you for your purchasing');
            setting_update_item('wallet_new_deposit_customer_content', CreditPaymentEmail::defaultNewBody());

            setting_update_item('wallet_update_deposit_admin_subject', 'Credit purchase updated');
            setting_update_item('wallet_update_deposit_admin_content', CreditPaymentEmail::defaultUpdateBody());
            setting_update_item('wallet_update_deposit_customer_subject', 'Your credit purchase updated');
            setting_update_item('wallet_update_deposit_customer_content', CreditPaymentEmail::defaultUpdateBody());

            Schema::table('bc_bookings', function (Blueprint $table) {
                if (!Schema::hasColumn('bc_bookings', 'wallet_credit_used')) {
                    $table->double('wallet_credit_used')->nullable();// Credit used
                    $table->double('wallet_total_used')->nullable();// Credit in total (after exchange credit to money)
                }
                if (!Schema::hasColumn('bc_bookings', 'wallet_transaction_id')) {
                    $table->bigInteger('wallet_transaction_id')->nullable();// Credit used
                }
                if (!Schema::hasColumn('bc_bookings', 'is_refund_wallet')) {
                    $table->tinyInteger('is_refund_wallet')->nullable();// Credit used
                }
            });

            Schema::table('bc_booking_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('bc_booking_payments', 'code')) {
                    $table->string('code', 64)->nullable();
                    $table->bigInteger('object_id')->nullable();
                    $table->string('object_model', 40)->nullable();
                    $table->text('meta')->nullable();
                }
                if (!Schema::hasColumn('bc_booking_payments', 'deleted_at')) {
                    $table->softDeletes();
                }
                if (!Schema::hasColumn('bc_booking_payments', 'wallet_transaction_id')) {
                    $table->bigInteger('wallet_transaction_id')->nullable();
                }
            });
            Schema::table('user_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('user_transactions', 'payment_id')) {
                    $table->bigInteger('payment_id')->nullable();
                }
                if (!Schema::hasColumn('user_transactions', 'booking_id')) {
                    $table->bigInteger('booking_id')->nullable();
                }

            });

            Schema::table('bc_spaces', function (Blueprint $table) {
                if (!Schema::hasColumn('bc_spaces', 'min_day_before_booking')) {
                    $table->integer('min_day_before_booking')->nullable();
                }
                if (!Schema::hasColumn('bc_spaces', 'min_day_stays')) {
                    $table->integer('min_day_stays')->nullable();
                }
            });

            Schema::table('bc_hotels', function (Blueprint $table) {
                if (!Schema::hasColumn('bc_hotels', 'min_day_before_booking')) {
                    $table->integer('min_day_before_booking')->nullable();
                }
                if (!Schema::hasColumn('bc_hotels', 'min_day_stays')) {
                    $table->integer('min_day_stays')->nullable();
                }
            });


            Settings::store('update_to_181', true);

            Schema::table((new Transaction())->getTable(), function (Blueprint $table) {
                $table->engine = 'InnoDB';
                if (!Schema::hasColumn((new Transaction())->getTable(), 'create_user')) {
                    $table->integer('create_user')->nullable();
                }
                if (!Schema::hasColumn((new Transaction())->getTable(), 'update_user')) {
                    $table->integer('update_user')->nullable();
                }

            });

            Schema::table((new Transfer())->getTable(), function (Blueprint $table) {
                $table->engine = 'InnoDB';
                if (!Schema::hasColumn((new Transfer())->getTable(), 'create_user')) {
                    $table->integer('create_user')->nullable();
                }
                if (!Schema::hasColumn((new Transfer())->getTable(), 'update_user')) {
                    $table->integer('update_user')->nullable();
                }
            });

            Schema::table((new Wallet())->getTable(), function (Blueprint $table) {
                $table->engine = 'InnoDB';
                if (!Schema::hasColumn((new Wallet())->getTable(), 'create_user')) {
                    $table->integer('create_user')->nullable();
                }
                if (!Schema::hasColumn((new Wallet())->getTable(), 'update_user')) {
                    $table->integer('update_user')->nullable();
                }
            });


            Settings::store('update_to_182', true);

        }

        public function updateTo190()
        {
            if (setting_item('update_to_190')) {
                return "Updated Up 1.9.0";
            }

            Artisan::call('migrate', [
                '--force' => true,
            ]);

            Schema::table(Tour::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(Tour::getTableName(), 'service_fee')) {
                    $table->tinyInteger('enable_service_fee')->nullable();
                    $table->text('service_fee')->nullable();
                }
            });
            Schema::table(Space::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(Space::getTableName(), 'service_fee')) {
                    $table->tinyInteger('enable_service_fee')->nullable();
                    $table->text('service_fee')->nullable();
                }
            });
            Schema::table(Hotel::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(Hotel::getTableName(), 'service_fee')) {
                    $table->tinyInteger('enable_service_fee')->nullable();
                    $table->text('service_fee')->nullable();
                }
            });
            Schema::table(Car::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(Car::getTableName(), 'service_fee')) {
                    $table->tinyInteger('enable_service_fee')->nullable();
                    $table->text('service_fee')->nullable();
                }
            });
            Schema::table(Event::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(Event::getTableName(), 'service_fee')) {
                    $table->tinyInteger('enable_service_fee')->nullable();
                    $table->text('service_fee')->nullable();
                }
            });

            Schema::table(Booking::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(Booking::getTableName(), 'vendor_service_fee_amount')) {
                    $table->decimal('vendor_service_fee_amount')->nullable();
                }
                if (!Schema::hasColumn(Booking::getTableName(), 'vendor_service_fee')) {
                    $table->text('vendor_service_fee')->nullable();
                }
            });


            if (!Schema::hasTable((new LocationCategory())->getTable())) {
                Schema::create((new LocationCategory())->getTable(), function (Blueprint $table) {
                    $table->bigIncrements('id');
                    $table->string('name', 255)->nullable();
                    $table->string('icon_class', 255)->nullable();
                    $table->text('content')->nullable();
                    $table->string('slug', 255)->nullable();
                    $table->string('status', 50)->nullable();
                    $table->nestedSet();

                    $table->integer('create_user')->nullable();
                    $table->integer('update_user')->nullable();
                    $table->softDeletes();

                    //Languages
                    $table->bigInteger('origin_id')->nullable();
                    $table->string('lang', 10)->nullable();

                    $table->timestamps();
                });
            }

            if (!Schema::hasTable((new LocationCategoryTranslation())->getTable())) {
                Schema::create((new LocationCategoryTranslation())->getTable(), function (Blueprint $table) {
                    $table->bigIncrements('id');
                    $table->bigInteger('origin_id')->nullable();
                    $table->string('locale', 10)->nullable();

                    $table->string('name', 255)->nullable();
                    $table->text('content')->nullable();

                    $table->integer('create_user')->nullable();
                    $table->integer('update_user')->nullable();
                    $table->unique(['origin_id', 'locale']);
                    $table->timestamps();
                });
            }

            Schema::table(Hotel::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(Hotel::getTableName(), 'surrounding')) {
                    $table->text('surrounding')->nullable();
                }
            });
            Schema::table(HotelTranslation::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(HotelTranslation::getTableName(), 'surrounding')) {
                    $table->text('surrounding')->nullable();
                }
            });

            Schema::table(Tour::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(Tour::getTableName(), 'surrounding')) {
                    $table->text('surrounding')->nullable();
                }
            });
            Schema::table(TourTranslation::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(TourTranslation::getTableName(), 'surrounding')) {
                    $table->text('surrounding')->nullable();
                }
            });

            Schema::table(Space::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(Space::getTableName(), 'surrounding')) {
                    $table->text('surrounding')->nullable();
                }
            });
            Schema::table(SpaceTranslation::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(SpaceTranslation::getTableName(), 'surrounding')) {
                    $table->text('surrounding')->nullable();
                }
            });
            Schema::table(Event::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(Event::getTableName(), 'surrounding')) {
                    $table->text('surrounding')->nullable();
                }
            });
            Schema::table(EventTranslation::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(EventTranslation::getTableName(), 'surrounding')) {
                    $table->text('surrounding')->nullable();
                }
            });

            Schema::table("users", function (Blueprint $table) {
                if (!Schema::hasColumn("users", 'user_name')) {
                    $table->string('user_name')->nullable()->unique();
                }
            });

            if (!Schema::hasTable((new Service())->getTable())) {
                Schema::create((new Service())->getTable(), function (Blueprint $table) {
                    $table->bigIncrements('id');

                    $table->string('title', 255)->nullable();
                    $table->string('slug', 255)->charset('utf8')->index();
                    $table->integer('category_id')->nullable();
                    $table->integer('location_id')->nullable();
                    $table->string('address', 255)->nullable();
                    $table->string('map_lat', 20)->nullable();
                    $table->string('map_lng', 20)->nullable();
                    $table->tinyInteger('is_featured')->nullable();
                    $table->tinyInteger('star_rate')->nullable();
                    //Price
                    $table->decimal('price', 12, 2)->nullable();
                    $table->decimal('sale_price', 12, 2)->nullable();

                    //Tour type
                    $table->integer('min_people')->nullable();
                    $table->integer('max_people')->nullable();
                    $table->integer('max_guests')->nullable();
                    $table->integer('review_score')->nullable();
                    $table->integer('min_day_before_booking')->nullable();
                    $table->integer('min_day_stays')->nullable();
                    $table->integer('object_id')->nullable();
                    $table->string('object_model', 255)->nullable();
                    $table->string('status', 50)->nullable();


                    $table->integer('create_user')->nullable();
                    $table->integer('update_user')->nullable();
                    $table->softDeletes();
                    $table->timestamps();
                });
            }

            Schema::table((new Service())->getTable(), function (Blueprint $table) {
                if (!Schema::hasColumn((new Service())->getTable(), 'status')) {
                    $table->string('status', 50)->nullable();
                }
            });

            if (!Schema::hasTable((new ServiceTranslation())->getTable())) {
                Schema::create((new ServiceTranslation())->getTable(), function (Blueprint $table) {
                    $table->bigIncrements('id');
                    $table->bigInteger('origin_id')->nullable();
                    $table->string('locale', 10)->nullable();

                    $table->string('title', 255)->nullable();
                    $table->text('address')->nullable();
                    $table->text('content')->nullable();

                    $table->integer('create_user')->nullable();
                    $table->integer('update_user')->nullable();
                    $table->unique(['origin_id', 'locale']);
                    $table->timestamps();
                });
            }
            $this->__seedLocationCategory();
            Settings::store('update_to_190', true);
            Artisan::call('cache:clear');
        }

        public function updateTo200()
        {
            $version = '2.0.9';
            if (version_compare(setting_item('update_to_200'), $version, '>=')) return;

            Artisan::call('migrate', [
                '--force' => true,
            ]);

            Schema::table('bc_attrs', function (Blueprint $table) {
                if (!Schema::hasColumn('bc_attrs', 'hide_in_filter_search')) {
                    $table->tinyInteger('hide_in_filter_search')->nullable();
                }
            });
            Schema::table('core_pages', function (Blueprint $table) {
                if (!Schema::hasColumn('core_pages', 'header_style')) {
                    $table->string('header_style',255)->nullable();
                }
                if (!Schema::hasColumn('core_pages', 'custom_logo')) {
                    $table->integer('custom_logo')->nullable();
                }
            });

            Schema::table('bc_events', function (Blueprint $table) {
                if (!Schema::hasColumn('bc_events', 'end_time')) {
                    $table->string('end_time',255)->nullable();
                }
                if (!Schema::hasColumn('bc_events', 'duration_unit')) {
                    $table->string('duration_unit',255)->nullable();
                }
            });

            if (!Schema::hasTable("bc_booking_time_slots")) {
                Schema::create("bc_booking_time_slots", function (Blueprint $table) {
                    $table->bigIncrements('id');

                    $table->integer('booking_id')->nullable();
                    $table->bigInteger('object_id')->nullable();
                    $table->string('object_model', 40)->nullable();
                    $table->time('start_time')->nullable();
                    $table->time('end_time')->nullable();
                    $table->float('duration',255)->nullable();
                    $table->string('duration_unit',255)->nullable();

                    $table->integer('create_user')->nullable();
                    $table->integer('update_user')->nullable();
                    $table->timestamps();
                });
            }

            Schema::table('bc_event_dates', function (Blueprint $table) {
                if (!Schema::hasColumn('bc_event_dates', 'price')) {
                    $table->decimal('price')->nullable();
                }
            });

            Schema::table('bc_tours', function (Blueprint $table) {
                if (!Schema::hasColumn('bc_tours', 'min_day_before_booking')) {
                    $table->integer('min_day_before_booking')->nullable();
                }
            });

            Schema::table('bc_hotel_rooms', function (Blueprint $table) {
                if (!Schema::hasColumn('bc_hotel_rooms', 'min_day_stays')) {
                    $table->integer('min_day_stays')->nullable();
                }
            });

            Schema::table('bc_attrs', function (Blueprint $table) {
                if (!Schema::hasColumn('bc_attrs', 'position')) {
                    $table->smallInteger('position')->nullable();
                }
            });

            setting_update_item('update_to_200',$version);
            Artisan::call('cache:clear');
        }

        public function updateTo210()
        {
            $version = '2.1.0';
            if (version_compare(setting_item('update_to_210'), $version, '>=')) return;

            Artisan::call('migrate', [
                '--force' => true,
            ]);

            Schema::table(Airport::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(Airport::getTableName(),'deleted_at')) {
                    $table->softDeletes();
                }
            });
            Schema::table(Airline::getTableName(), function (Blueprint $table) {
                if (!Schema::hasColumn(Airline::getTableName(),'deleted_at')) {
                    $table->softDeletes();
                }
            });

            Schema::table('bc_attrs', function (Blueprint $table) {
                if (!Schema::hasColumn('bc_attrs', 'position')) {
                    $table->smallInteger('position')->nullable();
                }
            });

            setting_update_item('update_to_200',$version);
            Artisan::call('cache:clear');
        }

        public function updateTo220()
        {
            $version = '2.2.0';
            if (version_compare(setting_item('update_to_220'), $version, '>=')) return;

            Artisan::call('migrate', [
                '--force' => true,
            ]);

            if(Schema::hasTable('messages') and !Schema::hasTable('ch_messages')){
                Schema::rename('messages','ch_messages');
            }
            if(Schema::hasTable('favorites') and !Schema::hasTable('ch_favorites')){
                Schema::rename('favorites','ch_favorites');
            }

            setting_update_item('update_to_220',$version);
            Artisan::call('cache:clear');
        }

        public function updateTo230()
        {
            $version = '2.3.4';
            if (version_compare(setting_item('update_to_230'), $version, '>=')) return;

            Artisan::call('migrate', [
                '--force' => true,
            ]);

            Schema::table('bc_cars', function (Blueprint $table) {
                if (!Schema::hasColumn('bc_cars', 'min_day_before_booking')) {
                    $table->integer('min_day_before_booking')->nullable();
                }
                if (!Schema::hasColumn('bc_cars', 'min_day_stays')) {
                    $table->integer('min_day_stays')->nullable();
                }
            });

            if (Schema::hasTable("user_wallets")) {
                Schema::table('user_wallets', function (Blueprint $table) {
                    if (!Schema::hasColumn('user_wallets', 'meta')) {
                        $table->text('meta')->nullable();
                    }
                });
            }
            $this->removeForeignKey();
            setting_update_item('update_to_230',$version);
            Artisan::call('cache:clear');
        }

        public function updateTo240()
        {
            $version = '2.4.0';
            if (version_compare(setting_item('update_to_240'), $version, '>=')) return;

            Artisan::call('migrate', [
                '--force' => true,
            ]);

            Permission::findOrCreate('coupon_view');
            Permission::findOrCreate('coupon_create');
            Permission::findOrCreate('coupon_update');
            Permission::findOrCreate('coupon_delete');
            $role = Role::findOrCreate('administrator');
            $role->givePermissionTo(Permission::all());


            Schema::table('bc_bookings', function (Blueprint $table) {
                if (!Schema::hasColumn('bc_bookings', 'total_before_discount')) {
                    $table->decimal('total_before_discount',10,2)->nullable()->default(0);
                }
                if (!Schema::hasColumn('bc_bookings', 'coupon_amount')) {
                    $table->decimal('coupon_amount',10,2)->nullable()->default(0);
                }
            });

            Schema::table('bc_service_translations', function (Blueprint $table) {
                if (!Schema::hasColumn('bc_service_translations', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
            $allServices = get_bookable_services();
            foreach ($allServices as $service) {
                $alls = $service::query()->orderBy('id', 'desc')->get();
                if (!empty($alls)) {
                    foreach ($alls as $item) {  $item->save();$item->update_service_rate();
                    }
                }
            }

            // Boat
            Permission::findOrCreate('boat_view');
            Permission::findOrCreate('boat_create');
            Permission::findOrCreate('boat_update');
            Permission::findOrCreate('boat_delete');
            Permission::findOrCreate('boat_manage_others');
            Permission::findOrCreate('boat_manage_attributes');

            $role = Role::findOrCreate('administrator');
            $role->givePermissionTo(Permission::all());

            setting_update_item('update_to_240',$version);
            Artisan::call('cache:clear');
        }

        public function checkDbEngine()
        {
            if (!setting_item('check_db_engine')) {
                $tables = [
                    (new Transaction())->getTable().'_1',
                    (new Transfer())->getTable().'_1',
                    (new Wallet())->getTable().'_1',
                ];
                foreach ($tables as $table) {
                    $engine = DB::select(DB::raw("select ENGINE,TABLE_NAME from information_schema.TABLES where TABLE_SCHEMA	= '".env('DB_DATABASE')."' and TABLE_NAME = '".$table."'"));
                    if (!empty($engine)) {
                        foreach ($engine as $value) {
                            if (!empty($value->ENGINE) and $value->ENGINE != 'InnoDB') {
                                DB::statement('ALTER TABLE '.$value->TABLE_NAME.' ENGINE = InnoDB');
                            }
                        }
                    }
                }
                Settings::store('check_db_engine', true);
            }
        }

        protected function __updateReviewVendorId()
        {
            $all = Review::query()->whereNull('vendor_id')->get();
            if (!empty($all)) {
                foreach ($all as $item) {
                    switch ($item->object_model) {
                        case "tour":
                            $tour = Tour::find($item->object_id);
                            if ($tour) {
                                $item->vendor_id = $tour->create_user;
                                $item->save();
                            }
                            break;
                        case "space":
                            $tour = Space::find($item->object_id);
                            if ($tour) {
                                $item->vendor_id = $tour->create_user;
                                $item->save();
                            }
                            break;
                    }
                }
            }
        }

        protected function __seedLocationCategory()
        {
            if (LocationCategory::query()->count() == 0) {
                $argv = [
                    [
                        'name'       => 'Education',
                        'icon_class' => 'icofont-education',
                        'status'     => 'publish'
                    ],
                    [
                        'name'       => 'Health',
                        'icon_class' => 'fa fa-hospital-o',
                        'status'     => 'publish'
                    ],
                    [
                        'name'       => 'Transportation',
                        'icon_class' => 'fa fa-subway',
                        'status'     => 'publish'
                    ],
                ];
                LocationCategory::insert($argv);
            }
        }

        protected function removeForeignKey(){
            try {
                $flightForeignKey = $this->getForeignKeyByTable(Flight::getTableName());
                Schema::table(Flight::getTableName(),function(Blueprint $blueprint)use ($flightForeignKey){
                    foreach ($flightForeignKey as $key){
                        $blueprint->dropForeign($key);

                    }
                });
                $flightSeatForeignKey = $this->getForeignKeyByTable(FlightSeat::getTableName());
                Schema::table(FlightSeat::getTableName(),function(Blueprint $blueprint) use ($flightSeatForeignKey){
                    foreach ($flightSeatForeignKey as $key){
                        $blueprint->dropForeign($key);

                    }
                });
                $bookingPassengersForeignKey = $this->getForeignKeyByTable(FlightSeat::getTableName());
                Schema::table(BookingPassengers::getTableName(),function(Blueprint $blueprint) use ($bookingPassengersForeignKey){
                    foreach ($bookingPassengersForeignKey as $key){
                        $blueprint->dropForeign($key);

                    }
                });
            }catch (\Exception $exception){
            }

        }

        protected function getForeignKeyByTable($tableName){
            $conn = Schema::getConnection()->getDoctrineSchemaManager();
            return array_map(function($key) {
                return $key->getName();
            }, $conn->listTableForeignKeys($tableName));
        }
    }
