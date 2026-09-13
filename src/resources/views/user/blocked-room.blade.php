<x-room.layout
  :title="'DrinkFlow - ' . __('room.blocked.title', ['name' => $room->name])"
  :room="$room"
  :room-user="$roomUser"
  :active-tab="'overview'"
>
  <!-- Blurred Background Room Workspace Content -->
  <div aria-hidden="true" class="pointer-events-none select-none filter blur-md opacity-30 space-y-6">
    <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs flex items-center justify-between">
      <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-[#006948] flex items-center justify-center font-bold text-2xl border border-emerald-100">
          <span class="material-symbols-outlined text-[28px]">corporate_fare</span>
        </div>
        <div>
          <h2 class="text-xl font-bold text-slate-900">{{ $room->name }}</h2>
          <p class="text-xs text-slate-500">{{ $room->description }}</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Centered Blocked Modal Component -->
  <x-room.blocked-modal :room="$room" :admin-user="$adminUser" />
</x-room.layout>
