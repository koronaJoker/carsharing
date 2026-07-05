@extends ('layouts/layout')

@section("content")


<div class="card">
    <img class = "main-image" src="{{ asset("images/home_image_header.png") }}" alt="carsharing">
    <div class="flex-right">
        <h1>Садись за руль</h1>
        <h2>Каршеринг нового поколения</h2>
        <h3> Автомобиль рядом — открой, забронируй и отправляйся в путь</h3>
        <a href = "/register" class="btn"><span>Начать</span></a>
    </div>

    
</div>
@endsection