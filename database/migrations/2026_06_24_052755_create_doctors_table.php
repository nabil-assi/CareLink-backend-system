    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        /**
         * Run the migrations.
         */
        public function up(): void
        {
            Schema::create('doctors', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('phone');
                $table->date('date_of_birth');
                $table->string('specialty');
                $table->text('address');
                $table->integer('years_of_experience');
                $table->string('credential_document');
                $table->string('profile_picture')->nullable();
                $table->string('national_id')->unique();  
                $table->enum('status', ['active', 'inactive']);
                $table->enum('gender', ['male', 'female']);
                $table->timestamps();
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('doctors');
        }
    };
