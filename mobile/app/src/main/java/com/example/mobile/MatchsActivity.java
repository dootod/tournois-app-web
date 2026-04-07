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
import com.example.mobile.model.Match;
import com.example.mobile.util.Fmt;

import java.util.ArrayList;
import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class MatchsActivity extends BaseActivity {

    private final List<Match> data = new ArrayList<>();
    private ArrayAdapter<Match> adapter;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_matchs);

        int tournoiId = getIntent().getIntExtra("tournoi_id", 0);
        String tournoiDate = getIntent().getStringExtra("tournoi_date");
        String monEquipe = getIntent().getStringExtra("mon_equipe");

        String title = tournoiDate != null ? Fmt.dateFr(tournoiDate) : "Mes matchs";
        if (monEquipe != null) title += " — " + monEquipe;
        setTitle(title);

        ListView list = findViewById(R.id.list);
        TextView emptyText = findViewById(R.id.emptyText);
        list.setEmptyView(emptyText);

        adapter = new ArrayAdapter<Match>(this, 0, data) {
            @NonNull
            @Override
            public View getView(int position, View convertView, @NonNull ViewGroup parent) {
                if (convertView == null) {
                    convertView = getLayoutInflater().inflate(R.layout.item_match, parent, false);
                }
                Match m = data.get(position);
                ((TextView) convertView.findViewById(R.id.phase)).setText(Fmt.phase(m.phase, m.round));

                String horaire = "";
                if (m.heure_debut != null) {
                    horaire = m.heure_debut;
                    if (m.heure_fin != null) horaire += " – " + m.heure_fin;
                }
                TextView heure = convertView.findViewById(R.id.heure);
                heure.setText(horaire);
                heure.setVisibility(horaire.isEmpty() ? View.GONE : View.VISIBLE);

                TextView tatami = convertView.findViewById(R.id.tatami);
                if (m.tatami != null) {
                    tatami.setText("Tatami " + m.tatami);
                    tatami.setVisibility(View.VISIBLE);
                } else {
                    tatami.setVisibility(View.GONE);
                }

                ((TextView) convertView.findViewById(R.id.adversaire)).setText("vs " + m.adversaire);
                return convertView;
            }
        };
        list.setAdapter(adapter);

        ApiClient.service().getMesMatchs(tournoiId).enqueue(new Callback<List<Match>>() {
            @Override public void onResponse(Call<List<Match>> call, Response<List<Match>> r) {
                if (r.isSuccessful() && r.body() != null) {
                    data.clear();
                    data.addAll(r.body());
                    adapter.notifyDataSetChanged();
                } else {
                    Toast.makeText(MatchsActivity.this, "Erreur " + r.code(), Toast.LENGTH_SHORT).show();
                }
            }
            @Override public void onFailure(Call<List<Match>> call, Throwable t) {
                Toast.makeText(MatchsActivity.this, "Réseau: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }
}
