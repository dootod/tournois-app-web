package com.example.mobile;

import android.os.Bundle;
import android.widget.ArrayAdapter;
import android.widget.ListView;
import android.widget.Toast;

import com.example.mobile.api.ApiClient;
import com.example.mobile.model.Score;

import java.util.ArrayList;
import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class ScoresActivity extends BaseActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_scores);
        setTitle("Mes scores");

        ListView list = findViewById(R.id.list);
        List<String> rows = new ArrayList<>();
        ArrayAdapter<String> adapter = new ArrayAdapter<>(this, android.R.layout.simple_list_item_1, rows);
        list.setAdapter(adapter);

        ApiClient.service().getMesScores().enqueue(new Callback<List<Score>>() {
            @Override public void onResponse(Call<List<Score>> call, Response<List<Score>> r) {
                if (r.isSuccessful() && r.body() != null) {
                    if (r.body().isEmpty()) {
                        rows.add("Aucun score enregistré pour le moment.");
                    } else {
                        for (Score s : r.body()) {
                            String line = "Score #" + s.id + " : " + s.score + " pts";
                            if (s.gagnant) line += " — 🏆 Gagnant";
                            if (s.disqualification) line += " — ⛔ DQ";
                            rows.add(line);
                        }
                    }
                    adapter.notifyDataSetChanged();
                } else {
                    Toast.makeText(ScoresActivity.this, "Erreur " + r.code(), Toast.LENGTH_SHORT).show();
                }
            }
            @Override public void onFailure(Call<List<Score>> call, Throwable t) {
                Toast.makeText(ScoresActivity.this, "Réseau: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }
}
