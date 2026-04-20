<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            目安箱
        </h2>
        <x-message :message="session('success')" />
    </x-slot>
    <div class="container mt-8">
    <div class="max-w-md mx-auto bg-white p-8 my-10 rounded-md shadow-md p-4">
        <form action="{{ route('contact.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="title" class="block text-gray-700 text-sm font-bold mb-2">タイトル</label>
                <input type="text" id="title" name="title" class="border rounded-md py-2 px-3 w-full focus:outline-none focus:ring focus:border-blue-300" required>
            </div>
            <div class="mb-6">
                <label for="message" class="block text-gray-700 text-sm font-bold mb-2">メッセージ</label>
                <textarea id="message" name="message" rows="10" class="border rounded-md py-2 px-3 w-full focus:outline-none focus:ring focus:border-blue-300" required></textarea>
            </div>
            <div class="w-full text-center">
                <button type="submit" class="bg-blue-500 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline hover:bg-blue-600">送信</button>
            </div>
        </form>
    </div>
    </div>
</x-app-layout>
