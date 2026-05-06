<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\StoreChannelRequest;
use App\Http\Requests\Web\Admin\UpdateChannelRequest;
use App\Models\Category;
use App\Models\Channel;
use App\Models\Playlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChannelManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.channels.index', [
            'channels' => Channel::query()
                ->with(['category', 'playlist', 'currentProgram'])
                ->withCount('programs')
                ->latest()
                ->paginate(20),
            'categories' => Category::query()->orderBy('name')->get(),
            'playlists' => Playlist::query()->latest()->get(),
        ]);
    }

    public function store(StoreChannelRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['group_title'] = Category::query()->find($data['category_id'] ?? null)?->name;
        $data['stream_hash'] = sha1(strtolower($data['stream_url']));
        $data['channel_identity_hash'] = sha1($data['playlist_id'].'|'.mb_strtolower($data['name']).'|'.mb_strtolower((string) $data['group_title']));

        DB::transaction(function () use ($data): void {
            $channel = Channel::query()->create($data);
            $channel->streams()->create([
                'stream_url' => $data['stream_url'],
                'stream_hash' => $data['stream_hash'],
                'stream_type' => $data['stream_type'],
                'priority' => 1,
                'is_active' => true,
                'label' => 'Primary',
            ]);
        });

        return back()->with('status', 'Channel created.');
    }

    public function edit(Channel $channel): View
    {
        return view('admin.channels.edit', [
            'channel' => $channel->load(['category', 'playlist']),
            'categories' => Category::query()->orderBy('name')->get(),
            'playlists' => Playlist::query()->latest()->get(),
        ]);
    }

    public function update(UpdateChannelRequest $request, Channel $channel): RedirectResponse
    {
        $data = $request->validated();
        $data['group_title'] = Category::query()->find($data['category_id'] ?? null)?->name;
        $data['stream_hash'] = sha1(strtolower($data['stream_url']));
        $data['channel_identity_hash'] = sha1($data['playlist_id'].'|'.mb_strtolower($data['name']).'|'.mb_strtolower((string) $data['group_title']));
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']).'-'.$channel->id;

        DB::transaction(function () use ($channel, $data): void {
            $channel->update($data);
            $channel->streams()->updateOrCreate(
                ['priority' => 1],
                [
                    'stream_url' => $data['stream_url'],
                    'stream_hash' => $data['stream_hash'],
                    'stream_type' => $data['stream_type'],
                    'is_active' => true,
                    'label' => 'Primary',
                ]
            );
        });

        return redirect()->route('admin.channels.index')->with('status', 'Channel updated.');
    }

    public function destroy(Channel $channel): RedirectResponse
    {
        $channel->delete();

        return back()->with('status', 'Channel deleted.');
    }
}
