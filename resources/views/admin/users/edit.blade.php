@extends('layouts.admin.app')
@section('Page Title', 'Edit User')

@section('title', 'Edit User')

@section('content')
    <div class="w-full ">

        <div class="w-full flex justify-center items-center mt-8">
            <form action="{{ route('users.update', $user->id) }}" method="post" class="w-1/2" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-2">
                        <label for="name" class="text-lg font-semibold text-custom-black">Enter the Name:</label>
                        <input type="text" name="name" id="name"
                            class="border-2 border-dark-gray rounded-md p-2 focus:outline-none focus:border-dark-orange"
                            value="{{ $user->name }}" required />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="email" class="text-lg font-semibold text-custom-black">Enter the Email:</label>
                        <input type="email" name="email" id="email"
                            class="border-2 border-dark-gray rounded-md p-2 focus:outline-none focus:border-dark-orange"
                            value="{{ $user->email }}" required />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="role" class="text-lg font-semibold text-custom-black">Choose the Roles:</label>
                        <div
                            class="border-2 border-dark-gray rounded-md p-2 focus:outline-none focus:border-dark-orange overflow-auto max-h-40">
                            @foreach ($roles as $role)
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                        class="form-checkbox h-5 w-5 text-dark-orange"
                                        {{ $user->roles->contains($role) ? 'checked' : '' }}>
                                    <span class="font-semibold mr-2 ml-1 uppercase">{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="doc"
                                class="text-lg font-semibold text-custom-black flex flex-col cursor-pointer">Upload
                                Signature: <span class="text-xs text-gray-600 font-normal">Less
                                    than 2MB (Optional)</span>
                                <div id="preview">
                                    {{-- @if (Storage::exists('public/signatures/' . $user->signature)) --}}
                                        <img src="{{ asset('storage/signatures/' . $user->signature) }}"
                                            alt="{{$user->name}}" class="h-32 w-48
                                    " />
                                    {{-- @endif --}}
                                </div>
                                <input type="file" name="signature" id="doc"
                                    class="border-2 border-dark-gray rounded-md p-2 focus:outline-none focus:border-dark-orange"
                                    hidden />

                            </label>



                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-link-button>
                            Update User
                        </x-link-button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@section('script')
    <script>
        //script for getting image preview
        const inpFile = document.getElementById('doc');
        const previewContainer = document.getElementById('preview');
        const previewImage = previewContainer.querySelector('img');

        inpFile.addEventListener('change', function() {
            const file = this.files[0];

            if (file) {
                const reader = new FileReader();

                previewImage.style.display = 'block';

                reader.addEventListener('load', function() {
                    previewImage.setAttribute('src', this.result);
                });

                reader.readAsDataURL(file);
            } else {
                previewImage.style.display = null;
                previewImage.setAttribute('src', '');
            }
        });
    </script>
@endsection
@endsection
