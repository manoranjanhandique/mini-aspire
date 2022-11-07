# MINI ASPIRE API

Mini Aspire is a fully functional REST API without any UI. As a backend used Laravel 9 and Laravel package **Sanctum** for authenticating API requests. Sanctum allows each user of application to generate multiple API tokens for their account.

## Technology use

- [Laravel 9](https://laravel.com/docs/9.x)
- [PostgreSQL](https://www.postgresql.org/)
- [Postman](https://www.postman.com/)

##### You have to just follow few steps to get following services :

We already assumed that you have Laravel  set up in your system. If not then install  [Composer](https://getcomposer.org/) first.

## Getting Started

#### Step 1 : Install [Wamp](https://www.wampserver.com/)/[Xampp](https://www.apachefriends.org/) Server.

#### Step 2 : Clone the Mini Aspire API project and put it in www/htdocs folder of Wamp/Xampp.

#### Step 3 : Install the [pgAdmin 4](https://www.pgadmin.org/download/) and set up with username and password.

#### Step 4: Setup database in .env file

> Set up as per your database 

```markdown
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=miniaspire
DB_USERNAME=aspire
DB_PASSWORD= root
```

#### Step 5: Run your database migrations.

> Database migration file already developed for this project. So just run following code in cmd.

```
php artisan migrate
```

#### Step 6 : Create the seeder for the User model

> I already created seeder for admin and customer as we did not create any register API.
>
> ../database/seeders

```markdown
UsersTablecustomerSeeder

public function run()
    {
        DB::table('users')->insert([
            'name' => 'aspire',
            'password' => Hash::make('password'),
            'role' => 2, //customer
        ]);
    }
    
UsersTableAdminSeeder

public function run()
    {
        DB::table('users')->insert([
            'name' => 'admin',
            'password' => Hash::make('password'),
            'role' => 1, //admin
        ]);
    }

```

> Now just run the following command
>
> For admin credentials

```markdown
php artisan db:seed --class=UsersTableAdminSeeder
```

> For customer credentials

```markdown
php artisan db:seed --class=UsersTablecustomerSeeder
```

#### Step 7: Test with postman, use following routes for get results

> Note : Already shared postman collection for test.

- ##### Customer Module

  > - **Log In**
  >
  >   As we already created credentials for login. 

  ```markdown
  http://127.0.0.1:8000/api/login
  username=	aspire
  password=	password
  ```

  > Results be like :
  >
  > {
  >
  >   "username": "aspire",
  >
  >   "message": "Login Successful",
  >
  >   "token": "1|0y04zGw2kzj4bA7kjUQoI10P2fPC7HfPLAt5E2aO"
  >
  > }

  > - **Customer dashboard**		
  >
  >   Note - Do not forget to use generated token.		

  ```markdown
  http://127.0.0.1:8000/api/customerprofile
  ```

  > - **Customer Loan Application**
  >
  >   Field used : **amount** =? **term** = ?

  ```markdown
  http://127.0.0.1:8000/api/reqloan
  ```

  > - **Customer installment repayments**
  >
  >   After approved the loan by admin customer can repayment with amount equal to the scheduled repayment. Field used : **due_amount**= ? **loan_no** = ?

  ```markdown
  http://127.0.0.1:8000/api/repaymentloan
  ```

- #### Admin Module

  > - Log in
  >
  >   As we already created credentials for login. 

  ```markdown
  http://127.0.0.1:8000/api/login
  username=	admin
  password=	password
  ```

  > - Admin dashboard

  ```markdown
  http://127.0.0.1:8000/api/adminprofile
  ```

  > - Loan Approved
  >
  >   Admin will approve the loan customer applied for. Field used : **loan_no**=? **loan_approved**= Y/N

  ```markdown
  http://127.0.0.1:8000/api/approveloan
  ```

  > - Log out

  ```markdown
  http://127.0.0.1:8000/api/logout
  ```

#### Step 8 : API or secure route

```markdown
Route::post("login",[App\Http\Controllers\Auth\AuthController::class,'checkAuth']);

// Authorized Admin
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
//all secure rotes of admin
});

// Authorized Customer
Route::middleware(['auth:sanctum', 'customer'])->group(function () {
//all secure rotes of customer
});
```

### Thank you