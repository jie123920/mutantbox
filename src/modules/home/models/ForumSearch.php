<?php

namespace app\modules\home\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * ForumSearch represents the model behind the search form about `app\modules\web\models\Forum`.
 */
class ForumSearch extends Forum {
    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'game_id', 'server_id', 'uid', 'topic_id', 'add_time', 'forum_ip', 'status'], 'integer'],
            [['forum_id', 'game_name', 'email', 'subject', 'descrption', 'clientinfo'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios() {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params) {
        $query = Forum::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'  => [
                'defaultOrder' => [
                    'add_time' => SORT_DESC,
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id'        => $this->id,
            'game_id'   => $this->game_id,
            'server_id' => $this->server_id,
            'uid'       => $this->uid,
            'topic_id'  => $this->topic_id,
            'add_time'  => $this->add_time,
            'forum_ip'  => $this->forum_ip,
            'status'    => $this->status,
        ]);

        $query->andFilterWhere(['like', 'forum_id', $this->forum_id])
            ->andFilterWhere(['like', 'game_name', $this->game_name])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'subject', $this->subject])
            ->andFilterWhere(['like', 'descrption', $this->descrption])
            ->andFilterWhere(['like', 'clientinfo', $this->clientinfo]);

        return $dataProvider;
    }
}
