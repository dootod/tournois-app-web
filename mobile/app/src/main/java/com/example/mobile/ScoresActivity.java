package com.example.mobile;

import android.os.Bundle;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ArrayAdapter;
import android.widget.ListView;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;

import com.example.mobile.api.ApiClient;
import com.example.mobile.model.Score;
import com.example.mobile.util.Fmt;

import java.util.ArrayList;
import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class ScoresActivity extends BaseActivity {

    private final List<Score> data = new ArrayList<>();
    private ArrayAdapter<Score> adapter;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_scores);
        setTitle("Mes scores");

        ListView list = findViewById(R.id.list);
        TextView emptyText = findViewById(R.id.emptyText);
        list.setEmptyView(emptyText);

        adapter = new ArrayAdapter<Score>(this, 0, data) {
            @NonNull
            @Override
            public View getView(int position, View convertView, @NonNull ViewGroup parent) {
                if (convertView == null) {
                    convertView = getLayoutInflater().inflate(R.layout.item_score, parent, false);
                }
                Score s = data.get(position);

                String title = "vs " + (s.adversaire != null ? s.adversaire : "Adversaire inconnu");
                ((TextView) convertView.findViewById(R.id.title)).setText(title);

                StringBuilder sub = new StringBuilder();
                if (s.date != null) sub.append("Tournoi du ").append(Fmt.dateFr(s.date));
                String ph = Fmt.phase(s.phase, s.round);
                if (!ph.isEmpty()) {
                    if (sub.length() > 0) sub.append(" · ");
                    sub.append(ph);
                }
                if (s.equipe != null) {
                    if (sub.length() > 0) sub.append(" · ");
                    sub.append("Équipe ").append(s.equipe);
                }
                ((TextView) convertView.findViewById(R.id.subtitle)).setText(sub.toString());

                TextView badge = convertView.findViewById(R.id.badge);
                badge.setVisibility(View.VISIBLE);
                if (s.disqualification) {
                    badge.setText("Disqualification");
                } else if (s.gagnant) {
                    badge.setText("Victoire");
                } else {
                    badge.setText("Défaite");
                }

                ((TextView) convertView.findViewById(R.id.score)).setText(String.valueOf(s.score));
                return convertView;
            }
        };
        list.setAdapter(adapter);

        ApiClient.service().getMesScores().enqueue(new Callback<List<Score>>() {
            @Override public void onResponse(Call<List<Score>> call, Response<List<Score>> r) {
                if (r.isSuccessful() && r.body() != null) {
                    data.clear();
                    data.addAll(r.body());
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
