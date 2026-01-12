import Model from 'flarum/common/Model';
import Season from './Season';

export default class Week extends Model {
  name = Model.attribute<string>('name');
  seasonId = Model.attribute<string | number | null>('seasonId');
  weekNumber = Model.attribute<number | null>('weekNumber');
  season = Model.hasOne<Season | false>('season');
}