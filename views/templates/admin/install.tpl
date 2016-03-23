<style rel="stylesheet">
  section.combinationdropbox label input {
    margin-left: 1em;
  }
  section.combinationdropbox .dividor {
    clear: both;
    float: none;
    width: 100%;
    height: 5px;
    background-image: -webkit-gradient(
        linear,
        right top,
        right bottom,
        color-stop(0, #CCCCCC),
        color-stop(0.5, #383F50),
        color-stop(1, #CCCCCC)
    );
    background-image: -o-linear-gradient(bottom, #CCCCCC 0%, #383F50 50%, #CCCCCC 100%);
    background-image: -moz-linear-gradient(bottom, #CCCCCC 0%, #383F50 50%, #CCCCCC 100%);
    background-image: -webkit-linear-gradient(bottom, #CCCCCC 0%, #383F50 50%, #CCCCCC 100%);
    background-image: -ms-linear-gradient(bottom, #CCCCCC 0%, #383F50 50%, #CCCCCC 100%);
    background-image: linear-gradient(to bottom, #CCCCCC 0%, #383F50 50%, #CCCCCC 100%);
    margin: 2em 0;
    border-radius: 10px;
  }

  section.combinationdropbox .progress {
    width: 300px;
    height: 25px;
    background: rgb(167, 197, 184);
    text-align: center;
    vertical-align: middle;
    line-height: 25px;
    color: rgb(94, 95, 69);
    background-image: -webkit-gradient( linear, left top, right bottom, color-stop(0, #8BCA54), color-stop(0.5, #56E64E), color-stop(1, #49FC35) );
    background-image: -o-linear-gradient(right bottom, #8BCA54 0%, #56E64E 50%, #49FC35 100%);
    background-image: -moz-linear-gradient(right bottom, #8BCA54 0%, #56E64E 50%, #49FC35 100%);
    background-image: -webkit-linear-gradient(right bottom, #8BCA54 0%, #56E64E 50%, #49FC35 100%);
    background-image: -ms-linear-gradient(right bottom, #8BCA54 0%, #56E64E 50%, #49FC35 100%);
    background-image: linear-gradient(to right bottom, #8BCA54 0%, #56E64E 50%, #49FC35 100%);
    background-size: 0% 100%;
    background-repeat: no-repeat;
    font-size: 15px;
    font-weight: bold;
  }

  section.combinationdropbox input[type=submit].stop {
    background-image: linear-gradient(to right bottom, #CA5454 0%, #E64E4E 100%);
    border: 3px double #CA5454;
  }
  section.combinationdropbox input[type=submit] {
    padding: 0.5em 2.5em;
    background-image: linear-gradient(to right bottom, #8BCA54 0%, #56E64E 100%);
    border: 3px double #8bca54;
    font-size: 14px;
    font-weight: bold;
  }

  section.combinationdropbox label {
    text-align: left;
    float: none;
  }

</style>
<section class="combinationdropbox">
  <h2>Installation progress</h2>
  <div class="progress">{$progress|default:0}%</div>
  <form method="post">
    <h3>Current processing state:</h3>
    Current value: <b>
      {if empty($cdbx.processing)}Idle
      {elseif $cdbx.processing == 1}Processing
      {elseif $cdbx.processing == -1}Stopping
      {/if}
    </b>
    {if empty($cdbx.processing)}
      <div>
        <input type="hidden" name="process" value="1">
        <input type="submit" value="Start">
      </div>
    {elseif $cdbx.processing == 1}
      <div>
        <input type="hidden" name="process" value="-1">
        <input type="submit" class="stop" value="Stop">
      </div>
      {elseif $cdbx.processing == -1}
      <div>
        <input type="hidden" name="process" value="1">
        <input type="submit" class="stop" value="Force start">
      </div>
    {/if}

    <div class="dividor"></div>
    <h3>Last processed product ID:</h3>
    Current value: <b>
      {if empty($cdbx.last_pid)}Not set
      {else}{$cdbx.last_pid}
      {/if}
    </b>

    <div class="dividor"></div>
    <h3>Starting product ID:</h3>
    <div>
      Current value: <b>
        {if empty($cdbx.start_pid)}Not set (default: 0)
        {else}{$cdbx.start_pid}
        {/if}
      </b>

      <div>
        <label>Set value
          <input type="number" name="CDBX_START_PID" value="{$cdbx.start_pid|default:0}">
        </label>
      </div>
    </div>

    <div class="dividor"></div>
    <h3>Max product ID:</h3>
    <div>
      Current value: <b>
        {if empty($cdbx.max_pid)}Not set
        {else}{$cdbx.max_pid}
        {/if}
      </b>
      <div>
        <label>Set value
          <input type="number" name="CDBX_MAX_PID" value="{$cdbx.max_pid|default:0}">
        </label>
      </div>
    </div>

    <div class="dividor"></div>
    <h3>Products per script execution</h3>
    <div>
      Current value: <b>
        {if empty($cdbx.length_pid)}Not set (default: {$cdbx.CDBX_LENGTH_PID})
        {else}{$cdbx.length_pid}
        {/if}
      </b>

      <div>
        <label>Set value
          <input type="number" name="CDBX_PID_CHUNK_LENGTH" value="{$cdbx.length_pid|default:$cdbx.CDBX_LENGTH_PID}">
        </label>
      </div>
    </div>

  </form>
</section>
