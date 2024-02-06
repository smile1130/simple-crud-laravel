<?php

// /////////////////////////////////////////////////////////////////////////////
// PLEASE DO NOT RENAME OR REMOVE ANY OF THE CODE BELOW.
// YOU CAN ADD YOUR CODE TO THIS FILE TO EXTEND THE FEATURES TO USE THEM IN YOUR WORK.
// /////////////////////////////////////////////////////////////////////////////

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Player;
use App\Models\PlayerSkill;
use Illuminate\Support\Facades\Validator;

class PlayerController extends Controller
{
    public function index()
    {
        // load all players
        $players = Player::all();

        // load the player skills
        $players->load('skills');

        // return the players
        return response()->json($players);
    }

    public function show($playerId)
    {
        // find the player
        $player = Player::findOrFail($playerId);

        // load the player skills
        $player->load('skills');

        // return the player
        return response()->json($player);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'position' => 'required|string|in:defender,midfielder,forward',
            'playerSkills' => 'required',
        ], [
            'position.required' => 'Invalid value for position: ' . $request->position,
            'position.in' => 'Invalid value for position: ' . $request->position,
            'playerSkills.required' => 'Players need at least one skill.'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }

        foreach ($request->playerSkills as $skill) {
            $validator = Validator::make($skill, [
                'skill' => 'required|string|in:defense,attack,speed,strength,stamina',
                'value' => 'required|integer|min:0|max:100'
            ], [
                'skill.required' => 'Invalid value for skill: ' . $skill['skill'],
                'skill.in' => 'Invalid value for skill: ' . $skill['skill'],
                'value.required' => 'Invalid value for value: ' . $skill['value'],
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()->first()], 400);
            }
        }


        // create the player
        $player = Player::create([
            'name' => $request->name,
            'position' => $request->position
        ]);

        // create the player skills
        foreach ($request->playerSkills as $skill) {
            PlayerSkill::create([
                'player_id' => $player->id,
                'skill' => $skill['skill'],
                'value' => $skill['value']
            ]);
        }

        $player->load('skills');

        // return the player
        return response()->json($player);
    }

    // update player function
    public function update(Request $request, $playerId)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'position' => 'required|string|in:defender,midfielder,forward',
            'playerSkills' => 'required',
        ], [
            'position.required' => 'Invalid value for position: ' . $request->position,
            'position.in' => 'Invalid value for position: ' . $request->position,
            'playerSkills.required' => 'Players need at least one skill.'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 400);
        }

        foreach ($request->playerSkills as $skill) {
            $validator = Validator::make($skill, [
                'skill' => 'required|string|in:defense,attack,speed,strength,stamina',
                'value' => 'required|integer|min:0|max:100'
            ], [
                'skill.required' => 'Invalid value for skill: ' . $skill['skill'],
                'skill.in' => 'Invalid value for skill: ' . $skill['skill'],
                'value.required' => 'Invalid value for value: ' . $skill['value'],
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()->first()], 400);
            }
        }

        // find the player
        $player = Player::findOrFail($playerId);

        // update the player
        $player->update([
            'name' => $request->name,
            'position' => $request->position
        ]);

        // update the player skills
        foreach ($request->playerSkills as $skill) {
            $playerSkill = PlayerSkill::where('player_id', $player->id)
                ->where('skill', $skill['skill'])
                ->first();

            if ($playerSkill) {
                $playerSkill->update(['value' => $skill['value']]);
            } else {
                PlayerSkill::create([
                    'player_id' => $player->id,
                    'skill' => $skill['skill'],
                    'value' => $skill['value']
                ]);
            }
        }

        // load the player skills
        $player->load('skills');

        // return the player
        return response()->json($player);
    }

    public function destroy($playerId)
    {
        // find the player
        $player = Player::findOrFail($playerId);

        // delete the player
        $player->delete();

        // return the success message
        return response()->json(['message' => 'Player deleted successfully.']);
    }

    public function processTeam(Request $request)
    {
        $requirements = $request->all();
        $team = [];

        foreach ($requirements as $requirement) {
            $position = $requirement['position'];
            $mainSkill = $requirement['mainSkill'];
            $numberOfPlayers = $requirement['numberOfPlayers'];

            $players = Player::where('position', $position)->get();

            if ($players->count() < $numberOfPlayers) {
                return response()->json(['message' => "Insufficient number of players for position: $position"], 400);
            }

            $bestPlayers = $players->filter(function ($player) use ($mainSkill) {
                return isset($player->skills[$mainSkill]);
            })->sortByDesc(function ($player) use ($mainSkill) {
                return $player->skills[$mainSkill];
            });

            if ($bestPlayers->count() < $numberOfPlayers) {
                $bestPlayers = $players->sortByDesc(function ($player) {
                    return max($player->skills->toArray());
                });
            }

            $team = array_merge($team, $bestPlayers->take($numberOfPlayers)->all());
        }

        return response()->json($team);
    }
}
